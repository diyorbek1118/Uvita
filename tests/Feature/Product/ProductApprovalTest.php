<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Tests\TestCase;

class ProductApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
    }

    private function createStaff(StaffRole $role): Staff
    {
        return Staff::create([
            'name'      => $role->value,
            'email'     => "{$role->value}@uvita.uz",
            'password'  => Hash::make('password123'),
            'role'      => $role->value,
            'is_active' => true,
        ]);
    }

    private function withStaffToken(StaffRole $role): static
    {
        $staff = $this->createStaff($role);
        $token = $staff->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function createManagerProduct(Staff $manager, string $status = 'inactive'): Product
    {
        return Product::create([
            'name'        => 'Manager mahsuloti',
            'slug'        => 'manager-mahsulot-' . uniqid(),
            'description' => 'Tavsif',
            'price'       => 30000,
            'stock'       => 10,
            'status'      => $status,
            'images'      => json_encode(['img.jpg']),
            'category_id' => $this->category->id,
            'manager_id'  => $manager->id,
        ]);
    }

    // ─── GET /api/products ────────────────────────────────────────────────────

    public function test_public_sees_only_active_products(): void
    {
        Product::create([
            'name' => 'Active', 'slug' => 'active', 'description' => 'D',
            'price' => 1000, 'stock' => 5, 'status' => 'active',
            'images' => [], 'category_id' => $this->category->id,
        ]);
        Product::create([
            'name' => 'Inactive', 'slug' => 'inactive', 'description' => 'D',
            'price' => 1000, 'stock' => 5, 'status' => 'inactive',
            'images' => [], 'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ─── PUT /api/admin/products/{id}/approve ─────────────────────────────────

    public function test_admin_can_approve_pending_product(): void
    {
        Queue::fake();
        $manager = $this->createStaff(StaffRole::MANAGER);
        $product = $this->createManagerProduct($manager);

        $response = $this->withStaffToken(StaffRole::ADMIN)
            ->putJson("/api/admin/products/{$product->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id'     => $product->id,
            'status' => 'active',
        ]);
    }

    // ─── PUT /api/admin/products/{id}/reject ──────────────────────────────────

    public function test_admin_can_reject_product_with_reason(): void
    {
        Queue::fake();
        $manager = $this->createStaff(StaffRole::MANAGER);
        $product = $this->createManagerProduct($manager);

        $response = $this->withStaffToken(StaffRole::ADMIN)
            ->putJson("/api/admin/products/{$product->id}/reject", [
                'reason' => 'Rasm sifatsiz',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id'               => $product->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Rasm sifatsiz',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $manager = $this->createStaff(StaffRole::MANAGER);
        $product = $this->createManagerProduct($manager);

        $response = $this->withStaffToken(StaffRole::ADMIN)
            ->putJson("/api/admin/products/{$product->id}/reject", []);

        $response->assertStatus(422);
    }

    // ─── GET /api/admin/products/pending ──────────────────────────────────────

    public function test_admin_sees_pending_products(): void
    {
        $manager = $this->createStaff(StaffRole::MANAGER);
        $this->createManagerProduct($manager, 'inactive');

        $response = $this->withStaffToken(StaffRole::ADMIN)
            ->getJson('/api/admin/products/pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_cannot_approve_product(): void
    {
        $manager = $this->createStaff(StaffRole::MANAGER);
        $product = $this->createManagerProduct($manager);

        $response = $this->putJson("/api/admin/products/{$product->id}/approve");

        $response->assertStatus(401);
    }
}
