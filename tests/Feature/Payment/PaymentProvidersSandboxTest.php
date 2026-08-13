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

final class PaymentProvidersSandboxTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();
        config()->set('payment.test_mode', true);

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Sandbox']);
        $category = Category::create(['name' => 'Sandbox', 'slug' => 'sandbox']);
        $this->product = Product::create([
            'name' => 'Sandbox product',
            'slug' => 'sandbox-product',
            'description' => 'Test',
            'price' => 60000,
            'stock' => 5,
            'status' => 'active',
            'images' => [],
            'category_id' => $category->id,
        ]);
    }

    public function test_click_prepare_complete_and_duplicate_complete_flow(): void
    {
        $order = $this->createOrder('click');

        $prepare = $this->postJson('/api/payment/click/webhook', [
            'action' => 0,
            'click_trans_id' => 'click-sandbox-1',
            'merchant_trans_id' => $order->id,
            'amount' => $order->grand_total,
        ])->assertOk()
            ->assertJsonPath('error', 0);

        $complete = [
            'action' => 1,
            'click_trans_id' => 'click-sandbox-1',
            'merchant_trans_id' => $order->id,
            'merchant_prepare_id' => $prepare->json('merchant_prepare_id'),
            'amount' => $order->grand_total,
            'error' => 0,
        ];

        $this->postJson('/api/payment/click/webhook', $complete)
            ->assertOk()
            ->assertJsonPath('error', 0);
        $this->postJson('/api/payment/click/webhook', $complete)
            ->assertOk()
            ->assertJsonPath('error', 0);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 4]);
    }

    public function test_click_rejects_amount_mismatch_and_unknown_prepare(): void
    {
        $order = $this->createOrder('click');

        $this->postJson('/api/payment/click/webhook', [
            'action' => 0,
            'click_trans_id' => 'click-wrong-amount',
            'merchant_trans_id' => $order->id,
            'amount' => 1,
        ])->assertOk()
            ->assertJsonPath('error', -5001);

        $this->postJson('/api/payment/click/webhook', [
            'action' => 1,
            'click_trans_id' => 'click-unknown',
            'merchant_trans_id' => $order->id,
            'merchant_prepare_id' => 999999,
            'amount' => $order->grand_total,
            'error' => 0,
        ])->assertOk()
            ->assertJsonPath('error', -5010);
    }

    public function test_uzum_information_perform_and_duplicate_perform_flow(): void
    {
        $order = $this->createOrder('uzum');

        $this->postJson('/api/payment/uzum/webhook', [
            'method' => 'GetInformation',
            'orderId' => $order->id,
        ])->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('data.amount', (int) ($order->grand_total * 100));

        $perform = [
            'method' => 'PerformTransaction',
            'orderId' => $order->id,
            'transactionId' => 'uzum-sandbox-1',
        ];

        $this->postJson('/api/payment/uzum/webhook', $perform)
            ->assertOk()
            ->assertJsonPath('status', 0);
        $this->postJson('/api/payment/uzum/webhook', $perform)
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('transactionId', 'uzum-sandbox-1');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 4]);
    }

    private function createOrder(string $provider): OrderModel
    {
        $this->actingAs($this->customer, 'api')
            ->postJson('/api/orders', [
                'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
                'address' => [
                    'region' => 'Toshkent',
                    'district' => 'Yunusobod',
                    'street' => 'Navoiy',
                    'house' => '1',
                ],
                'phone' => $this->customer->phone,
                'delivery_time' => 'Ertaga 14:00',
                'payment_method' => $provider,
            ])
            ->assertCreated();

        return OrderModel::latest('id')->firstOrFail();
    }
}
