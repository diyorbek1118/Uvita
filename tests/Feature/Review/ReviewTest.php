<?php

declare(strict_types=1);

namespace Tests\Feature\Review;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User     $customer;
    private Product  $product;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

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

    private function asAdmin(): static
    {
        $admin = Staff::create([
            'name'      => 'Admin',
            'email'     => 'admin@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => StaffRole::ADMIN->value,
            'is_active' => true,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function createDeliveredOrder(): OrderModel
    {
        return OrderModel::create([
            'user_id'        => $this->customer->id,
            'status'         => 'delivered',
            'address'        => json_encode(['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1']),
            'phone'          => '+998901234567',
            'delivery_time'  => 'Ertaga',
            'total_price'    => 30000,
            'service_fee'    => 4500,
            'courier_fee'    => 10000,
            'grand_total'    => 34500,
        ]);
    }

    private function createPendingReview(OrderModel $order): ReviewModel
    {
        return ReviewModel::create([
            'order_id'   => $order->id,
            'user_id'    => $this->customer->id,
            'product_id' => $this->product->id,
            'rating'     => 4,
            'comment'    => 'Yaxshi',
            'status'     => 'pending',
            'is_visible' => false,
        ]);
    }

    // ─── GET /api/products/{id}/reviews ──────────────────────────────────────

    public function test_public_can_get_approved_product_reviews(): void
    {
        $order  = $this->createDeliveredOrder();
        ReviewModel::create([
            'order_id'   => $order->id,
            'user_id'    => $this->customer->id,
            'product_id' => $this->product->id,
            'rating'     => 5,
            'comment'    => 'Ajoyib',
            'status'     => 'approved',
            'is_visible' => true,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_pending_reviews_are_not_public(): void
    {
        $order = $this->createDeliveredOrder();
        $this->createPendingReview($order);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ─── POST /api/reviews ────────────────────────────────────────────────────

    public function test_customer_can_create_review_for_delivered_order(): void
    {
        $order = $this->createDeliveredOrder();

        $response = $this->asCustomer()->postJson('/api/reviews', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'rating'     => 5,
            'comment'    => 'Juda yaxshi',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating'   => 5,
            'status'   => 'pending',
        ]);
    }

    public function test_cannot_review_non_delivered_order(): void
    {
        $order = OrderModel::create([
            'user_id'        => $this->customer->id,
            'status'         => 'confirmed',
            'address'        => json_encode(['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1']),
            'phone'          => '+998901234567',
            'delivery_time'  => 'Ertaga',
            'total_price'    => 30000,
            'service_fee'    => 4500,
            'courier_fee'    => 10000,
            'grand_total'    => 34500,
        ]);

        $response = $this->asCustomer()->postJson('/api/reviews', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'rating'     => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_review_for_same_order(): void
    {
        $order = $this->createDeliveredOrder();
        $this->createPendingReview($order);

        $response = $this->asCustomer()->postJson('/api/reviews', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'rating'     => 4,
        ]);

        $response->assertStatus(422);
    }

    public function test_review_rating_must_be_between_1_and_5(): void
    {
        $order = $this->createDeliveredOrder();

        $response = $this->asCustomer()->postJson('/api/reviews', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'rating'     => 6,
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_create_review(): void
    {
        $order = $this->createDeliveredOrder();

        $response = $this->postJson('/api/reviews', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'rating'     => 4,
        ]);

        $response->assertStatus(401);
    }

    // ─── Admin: PUT /api/admin/reviews/{id}/approve ───────────────────────────

    public function test_admin_can_approve_review(): void
    {
        $order  = $this->createDeliveredOrder();
        $review = $this->createPendingReview($order);

        $response = $this->asAdmin()->putJson("/api/admin/reviews/{$review->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('reviews', [
            'id'         => $review->id,
            'status'     => 'approved',
            'is_visible' => true,
        ]);
    }

    // ─── Admin: PUT /api/admin/reviews/{id}/reject ────────────────────────────

    public function test_admin_can_reject_review_with_reason(): void
    {
        $order  = $this->createDeliveredOrder();
        $review = $this->createPendingReview($order);

        $response = $this->asAdmin()->putJson("/api/admin/reviews/{$review->id}/reject", [
            'reason' => 'Munosib emas',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('reviews', [
            'id'         => $review->id,
            'status'     => 'rejected',
            'is_visible' => false,
        ]);
    }

    // ─── GET /api/admin/reviews/pending ──────────────────────────────────────

    public function test_admin_can_list_pending_reviews(): void
    {
        $order = $this->createDeliveredOrder();
        $this->createPendingReview($order);

        $response = $this->asAdmin()->getJson('/api/admin/reviews/pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
