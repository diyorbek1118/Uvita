<?php

declare(strict_types=1);

namespace Tests\Feature\Manager;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

final class ManagerPanelTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->category = Category::create(['name' => 'Oziq-ovqat', 'slug' => 'oziq-ovqat']);
        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Test Customer']);
    }

    private function manager(string $suffix): Staff
    {
        return Staff::create([
            'name' => "Manager {$suffix}",
            'email' => "manager-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => StaffRole::MANAGER,
            'is_active' => true,
        ]);
    }

    private function staff(StaffRole $role, string $suffix): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function as(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('manager-test')->plainTextToken);
    }

    private function product(Staff $manager, string $slug, string $status = 'active', int $stock = 20): Product
    {
        return Product::create([
            'name' => "Mahsulot {$slug}",
            'slug' => $slug,
            'description' => 'Test tavsifi',
            'price' => 45000,
            'stock' => $stock,
            'status' => $status,
            'images' => [],
            'category_id' => $this->category->id,
            'manager_id' => $manager->id,
            'rejection_reason' => $status === 'rejected' ? 'Rasm sifatsiz' : null,
        ]);
    }

    private function productPayload(string $name = 'Yangilangan mahsulot'): array
    {
        return [
            'name' => $name,
            'description' => 'Yangi va to‘liq tavsif',
            'price' => 55000,
            'stock' => 15,
            'images' => ['https://example.com/product.jpg'],
            'category_id' => $this->category->id,
        ];
    }

    private function order(string $status): OrderModel
    {
        return OrderModel::create([
            'user_id' => $this->customer->id,
            'status' => $status,
            'address' => ['region' => 'Toshkent', 'district' => 'Yunusobod', 'street' => 'Navoiy', 'house' => '1'],
            'phone' => $this->customer->phone,
            'delivery_time' => 'Ertaga 14:00',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => 10000,
            'grand_total' => 115000,
        ]);
    }

    public function test_manager_can_access_only_manager_panel_sections(): void
    {
        $manager = $this->manager('permissions');
        $this->as($manager);

        $this->getJson('/api/dashboard/products')->assertOk();
        $this->getJson('/api/dashboard/orders')->assertOk();

        $this->postJson('/api/categories', ['name' => 'Yopiq'])->assertForbidden();
        $this->getJson('/api/dashboard/staff')->assertForbidden();
        $this->getJson('/api/dashboard/analytics/summary')->assertForbidden();
        $this->getJson('/api/admin/reviews/pending')->assertForbidden();
        $this->getJson('/api/super/transactions')->assertForbidden();
    }

    public function test_manager_sees_only_own_products_in_list_detail_and_low_stock(): void
    {
        $manager = $this->manager('owner');
        $other = $this->manager('other');
        $own = $this->product($manager, 'own', 'active', 0);
        $ownSecond = $this->product($manager, 'own-second', 'rejected', 30);
        $foreign = $this->product($other, 'foreign', 'active', 0);

        $this->as($manager)
            ->getJson('/api/dashboard/products')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $own->id])
            ->assertJsonFragment(['id' => $ownSecond->id])
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->getJson("/api/dashboard/products/{$own->id}")->assertOk();
        $this->getJson("/api/dashboard/products/{$foreign->id}")->assertNotFound();

        $this->getJson('/api/dashboard/products/low-stock?threshold=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_manager_cannot_update_another_manager_product_through_any_endpoint(): void
    {
        $manager = $this->manager('attacker');
        $owner = $this->manager('real-owner');
        $foreign = $this->product($owner, 'protected');

        $this->as($manager)
            ->putJson("/api/dashboard/products/{$foreign->id}", $this->productPayload())
            ->assertForbidden();

        $this->putJson("/api/products/{$foreign->id}", $this->productPayload())
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $foreign->id,
            'name' => $foreign->name,
            'manager_id' => $owner->id,
        ]);
    }

    public function test_manager_can_create_product_and_validation_is_enforced(): void
    {
        $manager = $this->manager('creator');

        $this->as($manager)
            ->postJson('/api/dashboard/products', $this->productPayload('Yangi mahsulot'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.manager.id', $manager->id);

        $this->assertDatabaseHas('products', [
            'name' => 'Yangi mahsulot',
            'manager_id' => $manager->id,
            'status' => 'inactive',
        ]);

        $this->postJson('/api/dashboard/products', [
            'name' => '',
            'description' => '',
            'price' => -1,
            'stock' => -1,
            'category_id' => 999999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'stock', 'category_id']);
    }

    public function test_manager_can_upload_supported_product_image(): void
    {
        Storage::fake('public');
        $manager = $this->manager('image');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $url = $this->as($manager)
            ->postJson('/api/dashboard/products/upload-image', [
                'image' => UploadedFile::fake()->createWithContent('manager-product.png', $png),
                'folder' => 'products',
            ])->assertCreated()
            ->json('data.url');

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        Storage::disk('public')->assertExists(str_replace('storage/', '', $path));

        $this->postJson('/api/dashboard/products/upload-image', [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable();
    }

    public function test_manager_edit_resubmits_active_and_rejected_products_for_moderation(): void
    {
        $manager = $this->manager('resubmit');
        $admin = $this->staff(StaffRole::ADMIN, 'moderator');
        $rejected = $this->product($manager, 'rejected-product', 'rejected');
        $active = $this->product($manager, 'active-product', 'active');

        $this->as($manager)
            ->putJson("/api/dashboard/products/{$rejected->id}", $this->productPayload('Tuzatilgan mahsulot'))
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.rejection_reason', null)
            ->assertJsonPath('message', 'Mahsulot yangilandi va moderatsiyaga yuborildi');

        $this->putJson("/api/products/{$active->id}", $this->productPayload('Faol mahsulot yangilandi'))
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.rejectionReason', null);

        $this->assertDatabaseHas('products', ['id' => $rejected->id, 'status' => 'inactive', 'rejection_reason' => null]);
        $this->assertDatabaseHas('products', ['id' => $active->id, 'status' => 'inactive', 'rejection_reason' => null]);

        $this->as($admin)
            ->getJson('/api/admin/products/pending')
            ->assertOk()
            ->assertJsonFragment(['id' => $rejected->id])
            ->assertJsonFragment(['id' => $active->id]);
    }

    public function test_manager_cannot_delete_or_moderate_products(): void
    {
        $manager = $this->manager('restricted');
        $product = $this->product($manager, 'manager-product', 'inactive');
        $this->as($manager);

        $this->deleteJson("/api/dashboard/products/{$product->id}")->assertForbidden();
        $this->putJson("/api/admin/products/{$product->id}/approve")->assertForbidden();
        $this->putJson("/api/admin/products/{$product->id}/reject", ['reason' => 'No'])->assertForbidden();
    }

    public function test_manager_order_lists_and_details_hide_pending_and_cancelled(): void
    {
        $manager = $this->manager('orders');
        $orders = [];
        foreach (['pending', 'paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered', 'delivery_issue', 'cancelled'] as $status) {
            $orders[$status] = $this->order($status);
        }

        $response = $this->as($manager)->getJson('/api/dashboard/orders')->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($orders['pending']->id));
        $this->assertFalse($ids->contains($orders['cancelled']->id));
        foreach (['paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered', 'delivery_issue'] as $visible) {
            $this->assertTrue($ids->contains($orders[$visible]->id));
        }

        $this->getJson('/api/manager/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orders['paid']->id);

        $this->getJson("/api/dashboard/orders/{$orders['pending']->id}")->assertNotFound();
        $this->getJson("/api/manager/orders/{$orders['pending']->id}")->assertNotFound();
        $this->getJson("/api/dashboard/orders/{$orders['paid']->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.financials');
    }

    public function test_manager_can_confirm_only_paid_order(): void
    {
        $manager = $this->manager('confirm');
        $paid = $this->order('paid');
        $pending = $this->order('pending');

        $this->as($manager)
            ->putJson("/api/manager/orders/{$paid->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', ['id' => $paid->id, 'status' => 'confirmed']);
        $this->assertNotNull($paid->fresh()->confirmed_at);

        $this->putJson("/api/manager/orders/{$pending->id}/confirm")
            ->assertUnprocessable();
    }

    public function test_manager_can_mark_only_confirmed_order_ready_with_note(): void
    {
        $manager = $this->manager('ready');
        $confirmed = $this->order('confirmed');
        $paid = $this->order('paid');

        $this->as($manager)
            ->putJson("/api/manager/orders/{$confirmed->id}/ready", ['courier_note' => '2 ta sovutilgan paket'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver')
            ->assertJsonPath('data.courier_note', '2 ta sovutilgan paket');

        $updated = $confirmed->fresh();
        $this->assertNotNull($updated->ready_at);
        $this->assertSame('2 ta sovutilgan paket', $updated->courier_note);

        $this->putJson("/api/manager/orders/{$paid->id}/ready")
            ->assertUnprocessable();
    }
}
