<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User $customer;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        // Buyurtma yaratishda geokodlash tashqi so'rov yubormasligi uchun
        Http::fake();
        $this->seedSettings();

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product = Product::create([
            'name' => 'Mahsulot',
            'slug' => 'mahsulot',
            'description' => 'Tavsif',
            'price' => 30000,
            'stock' => 10,
            'status' => 'active',
            'images' => [],
            'category_id' => $this->category->id,
        ]);
    }

    private function asCustomer(): static
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function asStaff(StaffRole $role): static
    {
        $staff = Staff::create([
            'name' => $role->value,
            'email' => "{$role->value}@uvita.uz",
            'password' => Hash::make('password'),
            'role' => $role->value,
            'is_active' => true,
        ]);
        $token = $staff->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function validOrderPayload(): array
    {
        return [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'address' => [
                'region' => 'Toshkent',
                'district' => 'Yunusobod',
                'street' => 'Navoiy',
                'house' => '1',
            ],
            'phone' => '+998901234567',
            'delivery_time' => 'Ertaga 14:00-18:00',
            'payment_method' => 'payme',
        ];
    }

    // ─── POST /api/orders ─────────────────────────────────────────────────────

    public function test_customer_can_create_order(): void
    {
        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_create_order_decreases_nothing_on_creation(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 10,
        ]);
    }

    public function test_create_order_clears_cart(): void
    {
        $this->asCustomer()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ])->assertOk();

        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $this->asCustomer()->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_cash_order_reserves_stock_without_decreasing_physical_stock(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';

        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();

        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->reserved_stock);
        $this->assertSame(8, $this->product->fresh()->available_stock);
        $this->assertNotNull(OrderModel::firstOrFail()->stock_reserved_at);
    }

    public function test_reserved_cash_stock_cannot_be_sold_to_another_customer(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $payload['items'][0]['quantity'] = 8;
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();

        $other = User::create(['phone' => '+998901234568', 'name' => 'Vali']);
        $otherToken = $other->createToken('test')->plainTextToken;
        $payload['phone'] = $other->phone;
        $payload['items'][0]['quantity'] = 3;
        $this->withHeaders(['Authorization' => "Bearer {$otherToken}"])
            ->postJson('/api/orders', $payload)
            ->assertUnprocessable();

        $this->assertSame(8, $this->product->fresh()->reserved_stock);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_create_order_requires_items(): void
    {
        $payload = $this->validOrderPayload();
        $payload['items'] = [];

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(422);
    }

    public function test_create_order_with_insufficient_stock_returns_422(): void
    {
        $payload = $this->validOrderPayload();
        $payload['items'][0]['quantity'] = 100;

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(401);
    }

    public function test_order_below_minimum_amount_is_rejected(): void
    {
        // 1 x 30 000 = 30 000 < 50 000 (min_order_amount)
        $payload = $this->validOrderPayload();
        $payload['items'][0]['quantity'] = 1;

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_below_product_minimum_quantity_is_rejected(): void
    {
        $this->product->update(['minimum_order_quantity' => 3, 'unit' => 'kg']);

        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(422)
            ->assertJsonPath('message', '"Mahsulot" mahsulotidan kamida 3 kg buyurtma qilishingiz kerak.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_pricing_does_not_add_internal_fees_to_customer_total(): void
    {
        // 2 x 30 000 = 60 000; ichki ushlanmalar mijoz narxiga qo'shilmaydi.
        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.total_price', 60000)
            ->assertJsonPath('data.service_fee', 0)
            ->assertJsonPath('data.grand_total', 60000)
            ->assertJsonMissingPath('data.delivery_price')  // yetkazish mijozdan olinmaydi
            ->assertJsonMissingPath('data.courier_fee');    // kuryer haqi mijozga ko'rinmaydi
    }

    // ─── GET /api/orders ──────────────────────────────────────────────────────

    public function test_customer_can_list_own_orders(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response = $this->asCustomer()->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ─── DELETE /api/orders/{id} (cancel) ────────────────────────────────────

    public function test_customer_can_cancel_pending_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();

        $response = $this->asCustomer()->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancelling_pending_cash_order_releases_reserved_stock(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();
        $this->assertSame(2, $this->product->fresh()->reserved_stock);

        $this->deleteJson("/api/orders/{$order->id}")->assertOk();

        $this->assertSame(0, $this->product->fresh()->reserved_stock);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_customer_cannot_cancel_paid_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();
        $order->update(['status' => 'paid']);

        $response = $this->asCustomer()->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422);
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();

        $other = User::create(['phone' => '+998901234568', 'name' => 'Vali']);
        $response = $this->actingAs($other, 'api')
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    // ─── Manager: PUT /api/manager/orders/{id}/confirm ───────────────────────

    public function test_manager_can_confirm_paid_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();
        $order->update(['status' => 'paid']);

        $response = $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/confirm");

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
    }

    public function test_manager_can_confirm_pending_cash_order(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();

        $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/confirm")
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'cash',
            'status' => 'pending',
        ]);
    }

    public function test_manager_can_cancel_pending_cash_order_and_release_reservation(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();
        $this->assertSame(2, $this->product->fresh()->reserved_stock);

        $this->asStaff(StaffRole::MANAGER)
            ->deleteJson("/api/manager/orders/{$order->id}")
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertSame(0, $this->product->fresh()->reserved_stock);
    }

    public function test_manager_can_edit_pending_cash_order_before_confirmation(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();

        $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/items", [
                'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
            ])
            ->assertOk()
            ->assertJsonPath('data.total_price', 90000)
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 30000,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'cash',
            'amount' => 9000000,
        ]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 10]);
        $this->assertSame(3, $this->product->fresh()->reserved_stock);
    }

    public function test_confirmed_cash_order_decrements_stock_only_when_marked_ready(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();

        $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/confirm")
            ->assertOk();
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->reserved_stock);

        $this->putJson("/api/manager/orders/{$order->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver');
        $this->assertSame(8, $this->product->fresh()->stock);
        $this->assertSame(0, $this->product->fresh()->reserved_stock);
        $this->assertNotNull($order->fresh()->stock_committed_at);
        $this->assertNull($order->fresh()->stock_released_at);

        $this->putJson("/api/manager/orders/{$order->id}/ready")
            ->assertUnprocessable();
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_cancelling_cash_delivery_issue_restores_committed_stock_once(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();
        $this->product->update(['stock' => 8]);
        $order->update([
            'status' => 'delivery_issue',
            'stock_committed_at' => now(),
        ]);
        $this->asStaff(StaffRole::ADMIN)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", ['action' => 'cancel'])
            ->assertOk();

        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_rescheduling_cash_delivery_issue_keeps_stock_committed(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();
        $this->product->update(['stock' => 8]);
        $order->update([
            'status' => 'delivery_issue',
            'stock_committed_at' => now(),
        ]);

        $this->asStaff(StaffRole::ADMIN)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", [
                'action' => 'reschedule',
                'delivery_time' => '2026-08-16 16:00',
            ])
            ->assertOk();

        $this->assertSame(8, $this->product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_cash_order_stays_confirmed_when_stock_is_insufficient_at_ready(): void
    {
        $payload = $this->validOrderPayload();
        $payload['payment_method'] = 'cash';
        $this->asCustomer()->postJson('/api/orders', $payload)->assertCreated();
        $order = OrderModel::firstOrFail();
        $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/confirm")
            ->assertOk();

        $this->product->update(['stock' => 1]);
        $this->putJson("/api/manager/orders/{$order->id}/ready")
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertSame(1, $this->product->fresh()->stock);
    }

    public function test_manager_cannot_edit_confirmed_or_online_pending_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload())->assertCreated();
        $online = OrderModel::firstOrFail();
        $items = [['product_id' => $this->product->id, 'quantity' => 3]];

        $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$online->id}/items", ['items' => $items])
            ->assertUnprocessable();

        $online->latestPayment()->update(['provider' => 'cash']);
        $online->update(['status' => 'confirmed']);
        $this->putJson("/api/manager/orders/{$online->id}/items", ['items' => $items])
            ->assertUnprocessable();
    }

    public function test_manager_cannot_confirm_pending_order(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();

        $response = $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/confirm");

        $response->assertStatus(422);
    }

    // ─── Manager: PUT /api/manager/orders/{id}/ready ─────────────────────────

    public function test_manager_can_mark_ready_to_deliver(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();
        $order->update(['status' => 'confirmed']);

        $response = $this->asStaff(StaffRole::MANAGER)
            ->putJson("/api/manager/orders/{$order->id}/ready");

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'ready_to_deliver']);
    }

    // ─── Admin: PUT /api/admin/orders/{id}/resolve-issue ──────────────────────

    public function test_admin_can_resolve_delivery_issue_reschedule(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();
        $order->update(['status' => 'delivery_issue']);

        $response = $this->asStaff(StaffRole::ADMIN)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", [
                'action' => 'reschedule',
                'delivery_time' => '2026-07-25 16:00',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'ready_to_deliver',
            'courier_id' => null,
            'delivery_time' => '2026-07-25 16:00',
            'not_found_count' => 0,
        ]);
    }

    public function test_reschedule_requires_new_delivery_time(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::firstOrFail();
        $order->update(['status' => 'delivery_issue']);

        $this->asStaff(StaffRole::ADMIN)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", ['action' => 'reschedule'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_time');
    }

    public function test_admin_can_resolve_delivery_issue_cancel(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $order = OrderModel::first();
        $order->update(['status' => 'delivery_issue']);

        $response = $this->asStaff(StaffRole::ADMIN)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", ['action' => 'cancel']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }
}
