<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

/**
 * Payme sandbox (test rejimi) uchun to'liq to'lov oqimi.
 * PAYMENT_TEST_MODE=true bo'lganda imzo test_key bilan tekshiriladi (bo'sh → skip).
 */
class PaymeSandboxTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User    $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();

        config()->set('payment.test_mode', true);
        config()->set('payment.payme.test_key', ''); // sandbox: imzo skip
        config()->set('payment.payme.id', 'test_merchant');
        config()->set('payment.payme.checkout', 'https://checkout.test.paycom.uz');

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $category       = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product  = Product::create([
            'name'        => 'Mahsulot',
            'slug'        => 'mahsulot',
            'description' => 'Tavsif',
            'price'       => 30000,
            'stock'       => 10,
            'status'      => 'active',
            'images'      => [],
            'category_id' => $category->id,
        ]);
    }

    private function asCustomer(): static
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function createPendingOrder(): OrderModel
    {
        $this->asCustomer()->postJson('/api/orders', [
            'items'          => [['product_id' => $this->product->id, 'quantity' => 2]],
            'address'        => ['region' => 'Toshkent', 'district' => 'Yunusobod', 'street' => 'Navoiy', 'house' => '1'],
            'phone'          => '+998901234567',
            'delivery_time'  => 'Ertaga 14:00-18:00',
            'payment_method' => 'payme',
        ])->assertStatus(201);

        return OrderModel::latest('id')->firstOrFail();
    }

    /** Payme JSON-RPC webhook so'rovi — test Authorization header bilan */
    private function payme(string $method, array $params, int $id): \Illuminate\Testing\TestResponse
    {
        $auth = 'Basic ' . base64_encode('Paycom:'); // test_key bo'sh

        return $this->withHeaders(['Authorization' => $auth])
            ->postJson('/api/payment/payme/webhook', [
                'method' => $method,
                'params' => $params,
                'id'     => $id,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function test_payment_create_url_contains_real_amount_not_zero(): void
    {
        $order    = $this->createPendingOrder();
        $expected = (int) ($order->grand_total * 100);

        $response = $this->asCustomer()->postJson('/api/payment/create', [
            'order_id' => $order->id,
            'provider' => 'payme',
        ])->assertStatus(200);

        $url     = $response->json('data.payment_url');
        $encoded = substr($url, (int) strrpos($url, '/') + 1);
        $decoded = base64_decode($encoded, true) ?: '';

        $this->assertStringContainsString("a={$expected}", $decoded);
        $this->assertStringNotContainsString('a=0;', $decoded . ';');
        $this->assertStringStartsWith('https://checkout.test.paycom.uz/', $url);

        // Payment yozuvi ham to'g'ri summada
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'payme',
            'amount'   => $expected,
            'status'   => 'pending',
        ]);
    }

    public function test_full_sandbox_flow_marks_order_paid_and_decrements_stock(): void
    {
        $order  = $this->createPendingOrder();
        $amount = (int) ($order->grand_total * 100);
        $txId   = 'txn_sandbox_001';

        // 1. CheckPerformTransaction → allow
        $this->payme('CheckPerformTransaction', [
            'amount'  => $amount,
            'account' => ['order_id' => $order->id],
        ], 1)->assertStatus(200)->assertJsonPath('result.allow', true);

        // 2. CreateTransaction → state 1
        $this->payme('CreateTransaction', [
            'id'      => $txId,
            'time'    => now()->getTimestampMs(),
            'amount'  => $amount,
            'account' => ['order_id' => $order->id],
        ], 2)->assertStatus(200)->assertJsonPath('result.state', 1);

        // 3. PerformTransaction → state 2 (to'landi)
        $this->payme('PerformTransaction', ['id' => $txId], 3)
            ->assertStatus(200)
            ->assertJsonPath('result.state', 2);

        // Order paid, stock kamaydi (10 - 2 = 8)
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 8]);
        $this->assertDatabaseHas('payments', [
            'order_id'       => $order->id,
            'transaction_id' => $txId,
            'status'         => 'paid',
        ]);
    }

    public function test_perform_transaction_is_idempotent(): void
    {
        $order  = $this->createPendingOrder();
        $amount = (int) ($order->grand_total * 100);
        $txId   = 'txn_sandbox_002';

        $this->payme('CreateTransaction', [
            'id' => $txId, 'time' => now()->getTimestampMs(),
            'amount' => $amount, 'account' => ['order_id' => $order->id],
        ], 1);

        // Ikki marta PerformTransaction
        $this->payme('PerformTransaction', ['id' => $txId], 2)->assertJsonPath('result.state', 2);
        $this->payme('PerformTransaction', ['id' => $txId], 3)->assertJsonPath('result.state', 2);

        // Stock faqat bir marta kamaygan bo'lishi kerak
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 8]);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        $order = $this->createPendingOrder();

        $this->payme('CheckPerformTransaction', [
            'amount'  => 100, // noto'g'ri
            'account' => ['order_id' => $order->id],
        ], 1)->assertStatus(200)->assertJsonPath('error.code', -31001);
    }
}
