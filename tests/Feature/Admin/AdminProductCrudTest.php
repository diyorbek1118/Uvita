<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Tests\TestCase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
    }

    private function asRole(StaffRole $role): static
    {
        $staff = Staff::create([
            'name' => $role->value,
            'email' => $role->value.'-product@uvita.uz',
            'password' => Hash::make('password123'),
            'role' => $role->value,
            'is_active' => true,
        ]);

        return $this->withToken($staff->createToken('test')->plainTextToken);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Admin mahsuloti',
            'description' => 'To‘liq tavsif',
            'price' => 45000,
            'stock' => 12,
            'category_id' => $this->category->id,
            'images' => ['https://example.com/product.jpg'],
        ], $overrides);
    }

    public function test_admin_created_product_is_inactive_and_visible_in_pending_list(): void
    {
        $created = $this->asRole(StaffRole::ADMIN)
            ->postJson('/api/dashboard/products', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.manager', null);

        $id = $created->json('data.id');

        $this->getJson('/api/admin/products/pending')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $id]);
    }

    public function test_product_create_validates_required_and_boundary_values(): void
    {
        $this->asRole(StaffRole::ADMIN)
            ->postJson('/api/dashboard/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price', 'category_id']);

        $this->postJson('/api/dashboard/products', $this->payload([
            'price' => -1,
            'stock' => -1,
            'category_id' => 999999,
            'images' => ['not-a-url'],
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'stock', 'category_id', 'images.0']);
    }

    public function test_admin_can_update_every_product_field(): void
    {
        $id = $this->asRole(StaffRole::ADMIN)
            ->postJson('/api/dashboard/products', $this->payload())
            ->json('data.id');

        $other = Category::create(['name' => 'Boshqa', 'slug' => 'boshqa']);

        $this->putJson("/api/dashboard/products/{$id}", $this->payload([
            'name' => 'Yangilangan',
            'description' => 'Yangi tavsif',
            'price' => 99000,
            'stock' => 0,
            'category_id' => $other->id,
            'images' => ['https://example.com/new.jpg'],
        ]))->assertStatus(200)
            ->assertJsonPath('data.name', 'Yangilangan')
            ->assertJsonPath('data.price', 99000)
            ->assertJsonPath('data.stock', 0)
            ->assertJsonPath('data.category.id', $other->id);
    }

    public function test_admin_can_soft_delete_product_and_public_cannot_see_it(): void
    {
        $product = Product::create(array_merge($this->payload(), [
            'slug' => 'active-product',
            'status' => 'active',
        ]));

        $this->asRole(StaffRole::ADMIN)
            ->deleteJson("/api/dashboard/products/{$product->id}")
            ->assertStatus(204);

        $this->getJson("/api/products/{$product->id}")->assertStatus(404);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_manager_cannot_delete_product(): void
    {
        $product = Product::create(array_merge($this->payload(), [
            'slug' => 'manager-delete',
            'status' => 'inactive',
        ]));

        $this->asRole(StaffRole::MANAGER)
            ->deleteJson("/api/dashboard/products/{$product->id}")
            ->assertStatus(403);
    }

    public function test_low_stock_endpoint_includes_zero_and_threshold_stock(): void
    {
        foreach ([0, 10, 11] as $stock) {
            Product::create(array_merge($this->payload(), [
                'name' => "Stock {$stock}",
                'slug' => "stock-{$stock}",
                'stock' => $stock,
                'status' => 'active',
            ]));
        }

        $this->asRole(StaffRole::ADMIN)
            ->getJson('/api/dashboard/products/low-stock?threshold=10')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/dashboard/products/low-stock?threshold=-1')
            ->assertStatus(422);
    }

    public function test_image_upload_accepts_supported_image_and_rejects_invalid_files(): void
    {
        Storage::fake('public');
        $this->asRole(StaffRole::ADMIN);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $url = $this->postJson('/api/dashboard/products/upload-image', [
            'image' => UploadedFile::fake()->createWithContent('product.png', $png),
            'folder' => 'products',
        ])->assertStatus(201)->json('data.url');

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        Storage::disk('public')->assertExists(str_replace('storage/', '', $path));

        $this->postJson('/api/dashboard/products/upload-image', [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);

        $this->postJson('/api/dashboard/products/upload-image', [
            'image' => UploadedFile::fake()->createWithContent('large.png', $png.str_repeat('0', 6 * 1024 * 1024)),
        ])->assertStatus(422);
    }
}
