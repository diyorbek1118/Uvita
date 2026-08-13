<?php

declare(strict_types=1);

namespace Tests\Feature\Category;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): Staff
    {
        return Staff::create([
            'name' => 'Admin',
            'email' => 'admin@uvita.uz',
            'password' => Hash::make('password123'),
            'role' => StaffRole::ADMIN->value,
            'is_active' => true,
        ]);
    }

    private function actingAsAdmin(): static
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    // ─── GET /api/categories ──────────────────────────────────────────────────

    public function test_public_can_list_categories(): void
    {
        Category::create(['name' => 'Elektronika', 'slug' => 'elektronika', 'is_active' => true]);
        Category::create(['name' => 'Kiyim', 'slug' => 'kiyim', 'is_active' => true]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_categories_returns_paginated_data(): void
    {
        Category::create(['name' => 'Elektronika', 'slug' => 'elektronika']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    // ─── GET /api/categories/{id} ─────────────────────────────────────────────

    public function test_public_can_get_single_category(): void
    {
        $category = Category::create(['name' => 'Elektronika', 'slug' => 'elektronika']);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Elektronika');
    }

    public function test_get_non_existent_category_returns_404(): void
    {
        $response = $this->getJson('/api/categories/999');

        $response->assertStatus(404);
    }

    // ─── POST /api/categories ─────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/categories', [
            'name' => 'Elektronika',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Elektronika');

        $this->assertDatabaseHas('categories', ['name' => 'Elektronika']);
    }

    public function test_create_category_requires_name(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name', fn ($v) => ! empty($v));
    }

    public function test_unauthenticated_cannot_create_category(): void
    {
        $response = $this->postJson('/api/categories', ['name' => 'Test']);

        $response->assertStatus(401);
    }

    public function test_duplicate_slug_returns_422(): void
    {
        Category::create(['name' => 'Elektronika', 'slug' => 'elektronika']);

        $response = $this->actingAsAdmin()->postJson('/api/categories', [
            'name' => 'Elektronika2',
            'slug' => 'elektronika',
        ]);

        $response->assertStatus(422);
    }

    // ─── PUT /api/categories/{id} ─────────────────────────────────────────────

    public function test_admin_can_update_category(): void
    {
        $category = Category::create(['name' => 'Elektronika', 'slug' => 'elektronika']);

        $response = $this->actingAsAdmin()->putJson("/api/categories/{$category->id}", [
            'name' => 'Yangilangan Elektronika',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Yangilangan Elektronika');
    }

    // ─── DELETE /api/categories/{id} ─────────────────────────────────────────

    public function test_admin_can_delete_category(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);

        $response = $this->actingAsAdmin()->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_categories_can_be_filtered_by_parent_and_are_sorted(): void
    {
        $parent = Category::create(['name' => 'Asosiy', 'slug' => 'asosiy']);
        Category::create(['name' => 'Zebra', 'slug' => 'zebra', 'parent_id' => $parent->id]);
        Category::create(['name' => 'Alfa', 'slug' => 'alfa', 'parent_id' => $parent->id]);
        Category::create(['name' => 'Begona', 'slug' => 'begona']);

        $this->getJson("/api/categories?parent_id={$parent->id}")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alfa')
            ->assertJsonPath('data.1.name', 'Zebra');
    }

    public function test_admin_can_create_and_update_child_category_with_image(): void
    {
        $parent = Category::create(['name' => 'Asosiy', 'slug' => 'asosiy']);

        $created = $this->actingAsAdmin()->postJson('/api/categories', [
            'name' => 'Telefonlar',
            'image' => 'https://example.com/category.jpg',
            'parent_id' => $parent->id,
        ])->assertStatus(201);

        $id = $created->json('data.id');

        $this->putJson("/api/categories/{$id}", [
            'name' => 'Smartfonlar',
            'image' => 'https://example.com/smartphones.jpg',
            'parent_id' => $parent->id,
            'is_active' => false,
        ])->assertStatus(200)
            ->assertJsonPath('data.name', 'Smartfonlar')
            ->assertJsonPath('data.parentId', $parent->id)
            ->assertJsonPath('data.isActive', false);
    }

    public function test_category_cannot_be_its_own_parent_or_create_a_cycle(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $this->actingAsAdmin()->putJson("/api/categories/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $parent->id,
        ])->assertStatus(422);

        $this->putJson("/api/categories/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $child->id,
        ])->assertStatus(422);
    }

    public function test_deleting_parent_category_detaches_children(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $this->actingAsAdmin()->deleteJson("/api/categories/{$parent->id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Band', 'slug' => 'band']);
        $product = Product::create([
            'name' => 'Mahsulot',
            'slug' => 'mahsulot',
            'description' => 'Tavsif',
            'price' => 10000,
            'stock' => 5,
            'status' => 'active',
            'images' => [],
            'category_id' => $category->id,
        ]);

        $this->actingAsAdmin()->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_category_list_rejects_invalid_pagination(): void
    {
        $this->getJson('/api/categories?per_page=0')->assertStatus(422);
        $this->getJson('/api/categories?per_page=101')->assertStatus(422);
    }
}
