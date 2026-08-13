<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

final class SectionSixCriticalFlowTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seedSettings();

        config()->set('payment.test_mode', true);
        config()->set('payment.payme.test_key', '');

        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product = Product::create([
            'name' => 'Muhim mahsulot',
            'slug' => 'muhim-mahsulot',
            'description' => 'Test mahsuloti',
            'price' => 60000,
            'stock' => 20,
            'status' => 'active',
            'images' => [],
            'category_id' => $category->id,
        ]);
    }

    private function deliveryPin(OrderModel $order): string
    {
        $service = app(DeliveryConfirmationService::class);

        return $service->reveal($service->ensureForOrder($order->id));
    }

    public function test_complete_order_flow_preserves_status_sequence_and_timestamps(): void
    {
        $customer = $this->customer('+998901110001', 'Mijoz');
        $manager = $this->staff(StaffRole::MANAGER, 'full-flow');
        $admin = $this->staff(StaffRole::ADMIN, 'full-flow');
        $courier = $this->staff(StaffRole::COURIER, 'full-flow');
        $order = $this->createOrder($customer, $this->product);

        $this->payWithPayme($order, 'section6-full-flow');
        $this->assertOrderStatus($order, 'paid');

        $this->asStaff($manager)
            ->putJson("/api/manager/orders/{$order->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->putJson("/api/manager/orders/{$order->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver');

        $this->asStaff($admin)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver');

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering');

        $this->putJson("/api/courier/orders/{$order->id}/delivered", [
            'pin' => $this->deliveryPin($order),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $updated = $order->fresh();
        $this->assertSame('delivered', $updated->status->value);
        $this->assertSame($courier->id, $updated->courier_id);
        $this->assertNotNull($updated->paid_at);
        $this->assertNotNull($updated->confirmed_at);
        $this->assertNotNull($updated->ready_at);
        $this->assertNotNull($updated->delivering_at);
        $this->assertNotNull($updated->delivered_at);
        $this->assertTrue($updated->paid_at->lessThanOrEqualTo($updated->confirmed_at));
        $this->assertTrue($updated->confirmed_at->lessThanOrEqualTo($updated->ready_at));
        $this->assertTrue($updated->ready_at->lessThanOrEqualTo($updated->delivering_at));
        $this->assertTrue($updated->delivering_at->lessThanOrEqualTo($updated->delivered_at));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 19,
        ]);
    }

    public function test_three_failed_attempts_can_be_rescheduled_and_delivered_again(): void
    {
        [$order, $admin, $courier] = $this->orderInDeliveringState('reschedule');

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->asStaff($courier)
                ->putJson("/api/courier/orders/{$order->id}/not-found", [
                    'reason' => "{$attempt}-urinishda mijoz topilmadi",
                ])
                ->assertOk()
                ->assertJsonPath('data.not_found_count', $attempt);

            $response->assertJsonPath(
                'data.status',
                $attempt === 3 ? 'delivery_issue' : 'delivering'
            );
        }

        $newTime = '2026-07-26 16:00-18:00';
        $this->asStaff($admin)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", [
                'action' => 'reschedule',
                'delivery_time' => $newTime,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver')
            ->assertJsonPath('data.delivery_time', $newTime)
            ->assertJsonPath('data.not_found_count', 0);

        $this->assertNull($order->fresh()->courier_id);

        $this->putJson("/api/admin/orders/{$order->id}/assign-courier", [
            'courier_id' => $courier->id,
        ])->assertOk();

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk();

        $this->putJson("/api/courier/orders/{$order->id}/delivered", [
            'pin' => $this->deliveryPin($order),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
            'delivery_time' => $newTime,
            'not_found_count' => 0,
        ]);
    }

    public function test_cancelling_delivery_issue_starts_refund_and_keeps_states_consistent(): void
    {
        [$order, $admin, $courier] = $this->orderInDeliveringState('refund');

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->asStaff($courier)
                ->putJson("/api/courier/orders/{$order->id}/not-found", [
                    'reason' => 'Mijoz bilan bog‘lanib bo‘lmadi',
                ])
                ->assertOk();
        }

        $this->asStaff($admin)
            ->putJson("/api/admin/orders/{$order->id}/resolve-issue", ['action' => 'cancel'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'refund_pending');

        $updated = $order->fresh();
        $payment = PaymentModel::where('order_id', $order->id)->latest('id')->firstOrFail();

        $this->assertSame('cancelled', $updated->status->value);
        $this->assertNotNull($updated->cancelled_at);
        $this->assertSame('refund_pending', $payment->status->value);
        $this->assertNotNull($payment->refund_requested_at);
        $this->assertNull($payment->refunded_at);

        $this->asStaff($admin)
            ->getJson("/api/dashboard/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment.status', 'refund_pending')
            ->assertJsonPath(
                'data.payment.refund_requested_at',
                $payment->refund_requested_at?->toISOString()
            );
    }

    public function test_two_customers_cannot_buy_the_same_last_item(): void
    {
        $this->product->update(['stock' => 1]);
        $first = $this->createOrder(
            $this->customer('+998901110010', 'Birinchi mijoz'),
            $this->product
        );
        $second = $this->createOrder(
            $this->customer('+998901110011', 'Ikkinchi mijoz'),
            $this->product
        );

        $this->payWithPayme($first, 'section6-last-item-first');

        $amount = (int) ($second->grand_total * 100);
        $this->payme('CreateTransaction', [
            'id' => 'section6-last-item-second',
            'time' => now()->getTimestampMs(),
            'amount' => $amount,
            'account' => ['order_id' => $second->id],
        ], 20)->assertJsonPath('result.state', 1);

        $this->payme('PerformTransaction', [
            'id' => 'section6-last-item-second',
        ], 21)
            ->assertOk()
            ->assertJsonPath('error.code', -32400);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 0,
        ]);
        $this->assertDatabaseHas('orders', ['id' => $first->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $second->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $second->id,
            'status' => 'pending',
        ]);
        $this->assertGreaterThanOrEqual(0, $this->product->fresh()->stock);
    }

    public function test_click_and_uzum_duplicate_webhooks_change_stock_only_once(): void
    {
        $clickOrder = $this->createOrder(
            $this->customer('+998901110020', 'Click mijoz'),
            $this->product,
            'click'
        );

        $prepare = $this->postJson('/api/payment/click/webhook', [
            'action' => 0,
            'click_trans_id' => 'click-section6',
            'merchant_trans_id' => $clickOrder->id,
            'amount' => $clickOrder->grand_total,
        ])->assertOk()->assertJsonPath('error', 0);

        $completePayload = [
            'action' => 1,
            'click_trans_id' => 'click-section6',
            'merchant_trans_id' => $clickOrder->id,
            'merchant_prepare_id' => $prepare->json('merchant_prepare_id'),
            'amount' => $clickOrder->grand_total,
            'error' => 0,
        ];

        $this->postJson('/api/payment/click/webhook', $completePayload)
            ->assertOk()
            ->assertJsonPath('error', 0);
        $this->postJson('/api/payment/click/webhook', $completePayload)
            ->assertOk()
            ->assertJsonPath('error', 0);

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 19]);

        $uzumOrder = $this->createOrder(
            $this->customer('+998901110021', 'Uzum mijoz'),
            $this->product,
            'uzum'
        );
        $uzumPayload = [
            'method' => 'PerformTransaction',
            'orderId' => $uzumOrder->id,
            'transactionId' => 'uzum-section6',
        ];

        $this->postJson('/api/payment/uzum/webhook', $uzumPayload)
            ->assertOk()
            ->assertJsonPath('status', 0);
        $this->postJson('/api/payment/uzum/webhook', $uzumPayload)
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('transactionId', 'uzum-section6');

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 18]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $clickOrder->id,
            'status' => 'paid',
            'transaction_id' => 'click-section6',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $uzumOrder->id,
            'status' => 'paid',
            'transaction_id' => 'uzum-section6',
        ]);
    }

    /**
     * @return array{OrderModel, Staff, Staff}
     */
    private function orderInDeliveringState(string $suffix): array
    {
        $customer = $this->customer(
            $suffix === 'refund' ? '+998901110031' : '+998901110030',
            'Yetkazish mijoz'
        );
        $manager = $this->staff(StaffRole::MANAGER, $suffix);
        $admin = $this->staff(StaffRole::ADMIN, $suffix);
        $courier = $this->staff(StaffRole::COURIER, $suffix);
        $order = $this->createOrder($customer, $this->product);

        $this->payWithPayme($order, "section6-{$suffix}");
        $this->asStaff($manager)
            ->putJson("/api/manager/orders/{$order->id}/confirm")
            ->assertOk();
        $this->putJson("/api/manager/orders/{$order->id}/ready")->assertOk();
        $this->asStaff($admin)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])
            ->assertOk();
        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk();

        return [$order, $admin, $courier];
    }

    private function createOrder(
        User $customer,
        Product $product,
        string $provider = 'payme'
    ): OrderModel {
        $this->asCustomer($customer)
            ->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'address' => [
                    'region' => 'Toshkent',
                    'district' => 'Yunusobod',
                    'street' => 'Navoiy',
                    'house' => '1',
                ],
                'phone' => $customer->phone,
                'delivery_time' => 'Ertaga 14:00-18:00',
                'payment_method' => $provider,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        return OrderModel::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }

    private function payWithPayme(OrderModel $order, string $transactionId): void
    {
        $amount = (int) ($order->grand_total * 100);

        $this->payme('CreateTransaction', [
            'id' => $transactionId,
            'time' => now()->getTimestampMs(),
            'amount' => $amount,
            'account' => ['order_id' => $order->id],
        ], 1)
            ->assertOk()
            ->assertJsonPath('result.state', 1);

        $this->payme('PerformTransaction', ['id' => $transactionId], 2)
            ->assertOk()
            ->assertJsonPath('result.state', 2);
    }

    private function payme(string $method, array $params, int $id): TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('Paycom:'),
        ])->postJson('/api/payment/payme/webhook', [
            'method' => $method,
            'params' => $params,
            'id' => $id,
        ]);
    }

    private function asCustomer(User $customer): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($customer->createToken('section-six')->plainTextToken);
    }

    private function asStaff(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('section-six')->plainTextToken);
    }

    private function customer(string $phone, string $name): User
    {
        return User::create(['phone' => $phone, 'name' => $name]);
    }

    private function staff(StaffRole $role, string $suffix): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role->value,
            'is_active' => true,
        ]);
    }

    private function assertOrderStatus(OrderModel $order, string $status): void
    {
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $status,
        ]);
    }
}
