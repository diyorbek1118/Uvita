<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

final class SellerProductLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_seller_submits_product_and_admin_publishes_it(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Sabzavot', 'slug' => 'sabzavot']);
        $seller = $this->staff(StaffRole::SELLER, 'seller@uvita.uz');
        $admin = $this->staff(StaffRole::ADMIN, 'admin-seller@uvita.uz');
        SellerProfileModel::create($this->profile($seller->id, true));

        $this->withToken($seller->createToken('seller')->plainTextToken);
        $response = $this->post('/api/seller/products', $this->productPayload($category->id));
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.pricing.seller_net', 81000);

        $productId = (int) $response->json('data.product_id');
        $revisionId = (int) $response->json('data.id');
        $this->assertDatabaseHas('products', ['id' => $productId, 'status' => 'inactive', 'seller_id' => $seller->id]);

        $this->app['auth']->forgetGuards();
        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->putJson("/api/admin/product-revisions/{$revisionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson("/api/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.origin.farmer_name', 'Baraka fermer xo‘jaligi')
            ->assertJsonPath('data.minimum_order_quantity', 10);
    }

    public function test_active_product_keeps_old_market_data_while_edit_is_pending(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Meva', 'slug' => 'meva']);
        $seller = $this->staff(StaffRole::SELLER, 'seller-edit@uvita.uz');
        SellerProfileModel::create($this->profile($seller->id, true));
        $product = Product::create([
            'name' => 'Tasdiqlangan olma', 'slug' => 'tasdiqlangan-olma', 'description' => 'Eski tasdiqlangan tavsif',
            'price' => 20000, 'stock' => 100, 'status' => 'active', 'images' => ['old.jpg'],
            'category_id' => $category->id, 'seller_id' => $seller->id,
        ]);

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->post("/api/seller/products/{$product->id}/revisions", $this->productPayload($category->id, ['name' => 'Yangi olma', 'price' => 30000]))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Tasdiqlangan olma')
            ->assertJsonPath('data.price', 20000);
    }

    public function test_unverified_seller_cannot_submit_product(): void
    {
        $category = Category::create(['name' => 'Don', 'slug' => 'don']);
        $seller = $this->staff(StaffRole::SELLER, 'unverified@uvita.uz');
        SellerProfileModel::create($this->profile($seller->id, false));

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->post('/api/seller/products', $this->productPayload($category->id))
            ->assertForbidden();
    }

    public function test_seller_sees_only_orders_containing_own_products(): void
    {
        $category = Category::create(['name' => 'Poliz', 'slug' => 'poliz']);
        $seller = $this->staff(StaffRole::SELLER, 'seller-orders@uvita.uz');
        $otherSeller = $this->staff(StaffRole::SELLER, 'other-orders@uvita.uz');
        $buyer = User::create(['name' => 'Makro xaridor', 'phone' => '+998901111111']);
        $ownProduct = Product::create([
            'name' => 'Qovun', 'slug' => 'qovun', 'description' => 'Shirin qovun', 'price' => 10000,
            'stock' => 50, 'status' => 'active', 'images' => [], 'category_id' => $category->id, 'seller_id' => $seller->id,
        ]);
        $otherProduct = Product::create([
            'name' => 'Tarvuz', 'slug' => 'tarvuz', 'description' => 'Shirin tarvuz', 'price' => 8000,
            'stock' => 40, 'status' => 'active', 'images' => [], 'category_id' => $category->id, 'seller_id' => $otherSeller->id,
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $buyer->id, 'status' => 'paid', 'address' => json_encode(['line' => 'Toshkent']),
            'phone' => '+998901111111', 'delivery_time' => '12:00-14:00', 'total_price' => 66000,
            'service_fee' => 0, 'courier_fee' => 0, 'grand_total' => 66000, 'not_found_count' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            ['order_id' => $orderId, 'product_id' => $ownProduct->id, 'quantity' => 5, 'price' => 10000, 'created_at' => now(), 'updated_at' => now()],
            ['order_id' => $orderId, 'product_id' => $otherProduct->id, 'quantity' => 2, 'price' => 8000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->getJson('/api/seller/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderId)
            ->assertJsonPath('data.0.gross_amount', 50000)
            ->assertJsonPath('data.0.estimated_net_amount', 40500)
            ->assertJsonCount(1, 'data.0.items')
            ->assertJsonPath('data.0.items.0.product_name', 'Qovun');
    }

    private function staff(StaffRole $role, string $email): Staff
    {
        return Staff::create(['name' => $role->label(), 'email' => $email, 'password' => 'password123', 'role' => $role, 'is_active' => true]);
    }

    private function profile(int $sellerId, bool $verified): array
    {
        return [
            'seller_id' => $sellerId, 'business_name' => 'Baraka', 'legal_type' => 'farmer_farm',
            'tin' => '123456789'.$sellerId, 'phone' => '+998901234567', 'region' => 'Toshkent',
            'district' => 'Parkent', 'address' => 'Markaziy ko‘cha 1', 'bank_account' => '20208000900000000001',
            'bank_mfo' => '00001', 'terms_accepted' => true, 'is_verified' => $verified,
        ];
    }

    private function productPayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Organik pomidor',
            'description' => str_repeat('Haqiqiy fermer mahsuloti va saqlash sharoiti. ', 2),
            'price' => 100000,
            'stock' => 1000,
            'category_id' => $categoryId,
            'origin_region' => 'Parkent',
            'farmer_name' => 'Baraka fermer xo‘jaligi',
            'unit' => 'kg',
            'minimum_order_quantity' => 10,
            'images' => [
                UploadedFile::fake()->image('1.jpg', 1000, 1000),
                UploadedFile::fake()->image('2.jpg', 1000, 1000),
                UploadedFile::fake()->image('3.jpg', 1000, 1000),
                UploadedFile::fake()->image('4.jpg', 1000, 1000),
            ],
            'primary_image_index' => 0,
            'video' => UploadedFile::fake()->create('real-product.mp4', 1024, 'video/mp4'),
            'terms_accepted' => true,
        ], $overrides);
    }
}
