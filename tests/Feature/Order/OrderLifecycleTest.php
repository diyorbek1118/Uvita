<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User     $customer;
    private Category $category;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product  = Product::create([
            'name'        => 'Mahsulot',
            'slug'        => 'mahsulot',
            'description' => 'Tavsif',
            'price'       => 30000,
            'stock'       => 10,
            'status'      => 'active',
            'images'      => [],
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
            'name'      => $role->value,
            'email'     => "{$role->value}@uvita.uz",
            'password'  => Hash::make('password'),
            'role'      => $role->value,
            'is_active' => true,
        ]);
        $token = $staff->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function validOrderPayload(): array
    {
        return [
            'items'           => [['product_id' => $this->product->id, 'quantity' => 2]],
            'address'         => [
                'region'   => 'Toshkent',
                'district' => 'Yunusobod',
                'street'   => 'Navoiy',
                'house'    => '1',
            ],
            'phone'           => '+998901234567',
            'delivery_time'   => 'Ertaga 14:00-18:00',
            'payment_method'  => 'payme',
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
            'id'    => $this->product->id,
            'stock' => 10,
        ]);
    }

    public function test_create_order_clears_cart(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        Queue::assertPushed(\App\Jobs\ClearCartJob::class);
    }

    public function test_create_order_requires_items(): void
    {
        $payload           = $this->validOrderPayload();
        $payload['items']  = [];

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(422);
    }

    public function test_create_order_with_insufficient_stock_returns_422(): void
    {
        $payload                    = $this->validOrderPayload();
        $payload['items'][0]['quantity'] = 100;

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(401);
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

        $other    = User::create(['phone' => '+998901234568', 'name' => 'Vali']);
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
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", ['action' => 'reschedule']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivering']);
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
