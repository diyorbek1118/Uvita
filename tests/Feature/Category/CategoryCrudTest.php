<?php

declare(strict_types=1);

namespace Tests\Feature\Category;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): Staff
    {
        return Staff::create([
            'name'      => 'Admin',
            'email'     => 'admin@uvita.uz',
            'password'  => Hash::make('password123'),
            'role'      => StaffRole::ADMIN->value,
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
            ->assertJsonPath('errors.name', fn($v) => !empty($v));
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
}
