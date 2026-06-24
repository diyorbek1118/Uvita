<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User     $user;
    private Category $category;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user     = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product  = Product::create([
            'name'        => 'Mahsulot',
            'slug'        => 'mahsulot',
            'description' => 'Tavsif',
            'price'       => 20000,
            'stock'       => 10,
            'status'      => 'active',
            'images'      => [],
            'category_id' => $this->category->id,
        ]);
    }

    private function asUser(): static
    {
        $token = $this->user->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    // ─── GET /api/cart ────────────────────────────────────────────────────────

    public function test_get_empty_cart_returns_empty_data(): void
    {
        $response = $this->asUser()->getJson('/api/cart');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_cannot_get_cart(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    // ─── POST /api/cart/items ─────────────────────────────────────────────────

    public function test_user_can_add_item_to_cart(): void
    {
        $response = $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_add_same_product_merges_quantity(): void
    {
        $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 3,
        ]);

        $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);
    }

    public function test_add_exceeds_stock_returns_422(): void
    {
        $response = $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 100,
        ]);

        $response->assertStatus(422);
    }

    public function test_add_item_requires_product_id_and_quantity(): void
    {
        $response = $this->asUser()->postJson('/api/cart/items', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.product_id', fn($v) => !empty($v))
            ->assertJsonPath('errors.quantity', fn($v) => !empty($v));
    }

    public function test_add_inactive_product_returns_422(): void
    {
        $inactive = Product::create([
            'name'        => 'Inactive',
            'slug'        => 'inactive',
            'description' => 'D',
            'price'       => 1000,
            'stock'       => 5,
            'status'      => 'inactive',
            'images'      => [],
            'category_id' => $this->category->id,
        ]);

        $response = $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $inactive->id,
            'quantity'   => 1,
        ]);

        $response->assertStatus(422);
    }

    // ─── DELETE /api/cart/items ───────────────────────────────────────────────

    public function test_user_can_remove_item_from_cart(): void
    {
        $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        $response = $this->asUser()->deleteJson('/api/cart/items', [
            'product_id' => $this->product->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $this->product->id,
        ]);
    }

    public function test_remove_non_existent_item_returns_404(): void
    {
        $response = $this->asUser()->deleteJson('/api/cart/items', [
            'product_id' => $this->product->id,
        ]);

        $response->assertStatus(404);
    }

    // ─── DELETE /api/cart ─────────────────────────────────────────────────────

    public function test_user_can_clear_cart(): void
    {
        $this->asUser()->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        $response = $this->asUser()->deleteJson('/api/cart');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $this->product->id,
        ]);
    }
}
