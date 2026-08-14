<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

final class CustomerAppTest extends TestCase
{
    use RefreshDatabase;
    use SeedsSettings;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seedSettings();

        config()->set('payment.payme.id', 'payme-test');
        config()->set('payment.payme.checkout', 'https://checkout.test.paycom.uz');
        config()->set('payment.click.checkout', 'https://my.click.uz/services/pay');
        config()->set('payment.click.service_id', 'click-service');
        config()->set('payment.click.merchant_id', 'click-merchant');
        config()->set('payment.uzum.checkout', 'https://secure.apelsin.uz/open-services/checkout');
        config()->set('payment.uzum.service_id', 'uzum-service');

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product = Product::create([
            'name' => 'Mahsulot',
            'slug' => 'mahsulot',
            'description' => 'Tavsif',
            'price' => 30000,
            'stock' => 10,
            'status' => 'active',
            'images' => [],
            'category_id' => $category->id,
        ]);
    }

    private function asCustomer(?User $user = null): static
    {
        return $this->actingAs($user ?? $this->customer, 'api');
    }

    private function validPayload(string $provider = 'payme'): array
    {
        return [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'address' => [
                'region' => 'Toshkent',
                'district' => 'Yunusobod',
                'street' => 'Navoiy',
                'house' => '1',
            ],
            'phone' => '+998901234567',
            'delivery_time' => '2026-07-30 14:00',
            'payment_method' => $provider,
        ];
    }

    private function pendingOrder(User $user, string $provider = 'payme'): OrderModel
    {
        $this->asCustomer($user)
            ->postJson('/api/orders', $this->validPayload($provider))
            ->assertCreated();

        return OrderModel::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_checkout_requires_items_address_phone_time_and_payment_method(): void
    {
        $this->asCustomer()->postJson('/api/orders', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items',
                'address',
                'phone',
                'delivery_time',
                'payment_method',
            ]);
    }

    public function test_checkout_rejects_invalid_phone_payment_method_and_duplicate_products(): void
    {
        $payload = $this->validPayload();
        $payload['phone'] = '901234567';
        $payload['payment_method'] = 'visa';
        $payload['items'][] = $payload['items'][0];

        $this->asCustomer()->postJson('/api/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'phone',
                'payment_method',
                'items.1.product_id',
            ]);
    }

    public function test_inactive_product_cannot_be_ordered_directly(): void
    {
        $this->product->update(['status' => 'inactive']);

        $this->asCustomer()->postJson('/api/orders', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonPath('message', '"Mahsulot" mahsuloti hozir sotuvda emas.');
    }

    public function test_order_is_pending_and_contains_server_calculated_breakdown(): void
    {
        $this->asCustomer()->postJson('/api/orders', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', 60000)
            ->assertJsonPath('data.service_fee', 0)
            ->assertJsonPath('data.grand_total', 60000)
            ->assertJsonPath('data.items.0.product_name', 'Mahsulot')
            ->assertJsonPath('data.address.street', 'Navoiy');
    }

    public function test_cash_order_has_no_online_payment_url_and_keeps_amount_due(): void
    {
        $response = $this->asCustomer()
            ->postJson('/api/orders', $this->validPayload('cash'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.payment_url', null)
            ->assertJsonPath('data.grand_total', 60000);

        $this->assertDatabaseHas('payments', [
            'order_id' => $response->json('data.id'),
            'provider' => 'cash',
            'amount' => 6000000,
            'status' => 'pending',
        ]);
    }

    public function test_payme_click_and_uzum_checkout_urls_are_generated(): void
    {
        $providers = [
            'payme' => 'https://checkout.test.paycom.uz/',
            'click' => 'https://my.click.uz/services/pay?',
            'uzum' => 'https://secure.apelsin.uz/open-services/checkout?',
        ];

        foreach ($providers as $provider => $urlPrefix) {
            $user = User::create([
                'phone' => '+99890'.str_pad((string) (1234568 + count(OrderModel::all())), 7, '0', STR_PAD_LEFT),
                'name' => ucfirst($provider),
            ]);

            $response = $this->asCustomer($user)
                ->postJson('/api/orders', $this->validPayload($provider))
                ->assertCreated();

            $this->assertStringStartsWith($urlPrefix, $response->json('data.payment_url'));
        }
    }

    public function test_customer_only_sees_and_opens_own_orders(): void
    {
        $own = $this->pendingOrder($this->customer);
        $other = User::create(['phone' => '+998901234568', 'name' => 'Vali']);
        $otherOrder = $this->pendingOrder($other);

        $this->asCustomer()->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);

        $this->asCustomer()->getJson("/api/orders/{$own->id}")->assertOk();
        $this->asCustomer()->getJson("/api/orders/{$otherOrder->id}")->assertNotFound();
    }

    public function test_payment_create_cannot_be_used_for_another_customers_order(): void
    {
        $other = User::create(['phone' => '+998901234568', 'name' => 'Vali']);
        $otherOrder = $this->pendingOrder($other);

        $this->asCustomer()->postJson('/api/payment/create', [
            'order_id' => $otherOrder->id,
            'provider' => 'payme',
        ])->assertNotFound();
    }

    public function test_retry_payment_only_works_for_own_pending_order(): void
    {
        $order = $this->pendingOrder($this->customer, 'click');

        $this->asCustomer()->postJson("/api/orders/{$order->id}/pay/retry")
            ->assertOk()
            ->assertJsonPath('data.payment_url', fn ($url) => str_starts_with($url, 'https://my.click.uz/'));

        $order->update(['status' => 'paid']);
        $this->asCustomer()->postJson("/api/orders/{$order->id}/pay/retry")
            ->assertUnprocessable();
    }

    public function test_profile_has_complete_order_stats_and_name_update_propagates(): void
    {
        foreach (['pending', 'delivered', 'delivering', 'cancelled'] as $status) {
            OrderModel::create([
                'user_id' => $this->customer->id,
                'status' => $status,
                'address' => ['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1'],
                'phone' => $this->customer->phone,
                'delivery_time' => 'Ertaga',
                'total_price' => 60000,
                'service_fee' => 9000,
                'courier_fee' => 10000,
                'grand_total' => 69000,
            ]);
        }

        $this->asCustomer()->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ali')
            ->assertJsonPath('data.phone', '+998901234567')
            ->assertJsonPath('data.order_stats.total', 4)
            ->assertJsonPath('data.order_stats.delivered', 1)
            ->assertJsonPath('data.order_stats.pending', 1)
            ->assertJsonPath('data.order_stats.delivering', 1);

        $this->asCustomer()->putJson('/api/user/profile', ['name' => 'Alisher'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Alisher');

        $this->assertDatabaseHas('users', ['id' => $this->customer->id, 'name' => 'Alisher']);
    }

    public function test_profile_rejects_too_short_name(): void
    {
        $this->asCustomer()->putJson('/api/user/profile', ['name' => 'A'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
