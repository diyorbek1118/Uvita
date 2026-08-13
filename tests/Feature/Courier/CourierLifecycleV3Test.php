<?php

declare(strict_types=1);

namespace Tests\Feature\Courier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Infrastructure\Persistence\Models\CourierDevice;
use Modules\Courier\Infrastructure\Persistence\Models\CourierNotification;
use Modules\Courier\Infrastructure\Persistence\Models\CourierPayout;
use Modules\Courier\Infrastructure\Persistence\Models\CourierSupportTicket;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAttempt;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryProof;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

final class CourierLifecycleV3Test extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();
        $this->customer = User::create([
            'phone' => '+998901234567',
            'name' => 'Asosiy mijoz',
        ]);
    }

    private function staff(StaffRole $role, string $suffix): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function asStaff(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('courier-v3-test')->plainTextToken);
    }

    private function asCustomer(?User $customer = null): static
    {
        app('auth')->forgetGuards();
        $customer ??= $this->customer;

        return $this->withToken($customer->createToken('customer-v3-test')->plainTextToken);
    }

    private function order(
        ?Staff $courier,
        string $status = 'ready_to_deliver',
        ?User $customer = null,
        int $courierFee = 10000,
        mixed $deliveredAt = null,
    ): OrderModel {
        return OrderModel::create([
            'user_id' => ($customer ?? $this->customer)->id,
            'courier_id' => $courier?->id,
            'status' => $status,
            'address' => [
                'region' => 'Toshkent',
                'district' => 'Yunusobod',
                'street' => 'Navoiy',
                'house' => '1',
            ],
            'delivery_latitude' => 41.311081,
            'delivery_longitude' => 69.240562,
            'phone' => '+998901234567',
            'phone_secondary' => '+998909999999',
            'delivery_time' => 'Bugun 14:00',
            'courier_note' => '2 ta paket',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => $courierFee,
            'grand_total' => 115000,
            'not_found_count' => 0,
            'ready_at' => in_array($status, ['ready_to_deliver', 'delivering', 'delivered'], true) ? now() : null,
            'delivering_at' => in_array($status, ['delivering', 'delivered'], true) ? now() : null,
            'delivered_at' => $status === 'delivered' ? ($deliveredAt ?? now()) : null,
        ]);
    }

    private function assign(OrderModel $order, Staff $courier, Staff $admin): void
    {
        $this->asStaff($admin)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])
            ->assertOk();
    }

    public function test_profile_and_shift_are_managed_separately_and_active_delivery_blocks_shift_end(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'profile-shift');

        $this->asStaff($courier)
            ->putJson('/api/courier/profile', [
                'name' => 'Yangilangan Kuryer',
                'phone' => '+998935551122',
                'vehicle_type' => 'motorcycle',
                'vehicle_number' => '01 A 777 AA',
                'photo' => 'https://cdn.uvita.uz/couriers/1.jpg',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Yangilangan Kuryer')
            ->assertJsonPath('data.vehicle_type', 'motorcycle')
            ->assertJsonPath('data.is_online', false);

        $this->putJson('/api/courier/availability', ['is_online' => true])
            ->assertOk()
            ->assertJsonPath('data.is_online', true);

        $activeOrder = $this->order($courier, 'delivering');

        $this->putJson('/api/courier/availability', ['is_online' => false])
            ->assertUnprocessable();

        $this->assertTrue($courier->fresh()->is_active);
        $this->assertTrue((bool) $courier->fresh()->courierProfile?->is_online);

        $activeOrder->update(['status' => 'delivered', 'delivered_at' => now()]);

        $this->putJson('/api/courier/availability', ['is_online' => false])
            ->assertOk()
            ->assertJsonPath('data.is_online', false);

        $this->putJson('/api/courier/profile', [
            'name' => 'Xato Telefon',
            'phone' => '901234567',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_admin_assignment_is_audited_notified_and_courier_can_reject_it(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'assign');
        $courier = $this->staff(StaffRole::COURIER, 'assigned');
        $otherCourier = $this->staff(StaffRole::COURIER, 'reject-attacker');
        $order = $this->order(null);

        $this->assign($order, $courier, $admin);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
            'status' => 'ready_to_deliver',
        ]);
        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'assigned_by' => $admin->id,
            'status' => 'assigned',
        ]);
        $this->assertDatabaseHas('delivery_proofs', ['order_id' => $order->id]);
        $notification = CourierNotification::where('courier_id', $courier->id)->firstOrFail();

        $this->asStaff($courier)
            ->getJson('/api/courier/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'order_assigned')
            ->assertJsonPath('data.0.is_read', false);

        $this->putJson("/api/courier/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->asStaff($otherCourier)
            ->putJson("/api/courier/orders/{$order->id}/reject", ['reason' => 'Begona tayinlov'])
            ->assertNotFound();

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/reject", ['reason' => 'Transport nosoz'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready_to_deliver');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => null]);
        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'status' => 'rejected',
            'rejection_reason' => 'Transport nosoz',
        ]);
    }

    public function test_customer_owned_pin_has_attempt_lock_and_completes_delivery_with_proof(): void
    {
        config([
            'courier.delivery_pin.max_attempts' => 3,
            'courier.delivery_pin.block_minutes' => 10,
        ]);

        $admin = $this->staff(StaffRole::ADMIN, 'pin');
        $courier = $this->staff(StaffRole::COURIER, 'pin');
        $order = $this->order(null);
        $this->assign($order, $courier, $admin);

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.assignment.status', 'accepted');

        $otherCustomer = User::create(['phone' => '+998907777777', 'name' => 'Begona mijoz']);
        $this->asCustomer($otherCustomer)
            ->getJson("/api/orders/{$order->id}/delivery-code")
            ->assertNotFound();

        $pin = (string) $this->asCustomer()
            ->getJson("/api/orders/{$order->id}/delivery-code")
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->json('data.pin');
        $wrongPin = $pin === '0000' ? '1111' : '0000';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->asStaff($courier)
                ->putJson("/api/courier/orders/{$order->id}/delivered", ['pin' => $wrongPin])
                ->assertUnprocessable();
        }

        $lockedProof = DeliveryProof::where('order_id', $order->id)->firstOrFail();
        $this->assertNotNull($lockedProof->pin_locked_until);
        $this->assertSame(0, $lockedProof->pin_attempts);

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/delivered", ['pin' => $pin])
            ->assertUnprocessable();

        $this->travel(11)->minutes();

        $this->putJson("/api/courier/orders/{$order->id}/delivered", [
            'pin' => $pin,
            'recipient_name' => 'Dilshod Karimov',
            'latitude' => 41.311081,
            'longitude' => 69.240562,
        ])->assertOk()->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('delivery_proofs', [
            'order_id' => $order->id,
            'verified_by_courier_id' => $courier->id,
            'recipient_name' => 'Dilshod Karimov',
        ]);
        $this->assertNotNull(DeliveryProof::where('order_id', $order->id)->firstOrFail()->verified_at);

        $this->getJson('/api/courier/history')
            ->assertOk()
            ->assertJsonPath('data.0.phone', '+998***4567')
            ->assertJsonPath('data.0.phone_secondary', null)
            ->assertJsonPath('data.0.address.region', 'Toshkent')
            ->assertJsonMissingPath('data.0.address.street')
            ->assertJsonPath('data.0.delivery_location', null);
    }

    public function test_location_and_not_found_attempts_are_scoped_to_active_delivery(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'location');
        $courier = $this->staff(StaffRole::COURIER, 'location');
        $attacker = $this->staff(StaffRole::COURIER, 'location-attacker');
        $order = $this->order($courier, 'delivering');

        $this->asStaff($courier)
            ->postJson('/api/courier/locations', [
                'order_id' => $order->id,
                'latitude' => 41.300001,
                'longitude' => 69.200001,
                'accuracy' => 15,
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id);

        $this->asStaff($attacker)
            ->postJson('/api/courier/locations', [
                'order_id' => $order->id,
                'latitude' => 41.300002,
                'longitude' => 69.200002,
            ])->assertNotFound();

        $this->postJson('/api/courier/locations', [
            'order_id' => $order->id,
            'latitude' => 100,
            'longitude' => 69.2,
        ])->assertUnprocessable()->assertJsonValidationErrors('latitude');

        $this->asStaff($admin)
            ->getJson("/api/admin/couriers/{$courier->id}/location")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.accuracy', 15);

        $this->asStaff($courier)
            ->putJson("/api/courier/orders/{$order->id}/not-found", [
                'reason_code' => 'no_answer',
                'reason_note' => 'Uch marta qo‘ng‘iroq qilindi',
                'latitude' => 41.300003,
                'longitude' => 69.200003,
            ])
            ->assertOk()
            ->assertJsonPath('data.not_found_count', 1)
            ->assertJsonPath('data.attempts.0.reason_code', 'no_answer');

        $attempt = DeliveryAttempt::where('order_id', $order->id)->firstOrFail();
        $this->assertSame($courier->id, $attempt->courier_id);
        $this->assertSame('Uch marta qo‘ng‘iroq qilindi', $attempt->reason_note);

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $this->postJson('/api/courier/locations', [
            'order_id' => $order->id,
            'latitude' => 41.3,
            'longitude' => 69.2,
        ])->assertNotFound();
    }

    public function test_device_notification_and_support_records_are_owner_scoped(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'support');
        $courier = $this->staff(StaffRole::COURIER, 'support');
        $otherCourier = $this->staff(StaffRole::COURIER, 'support-other');
        $order = $this->order($courier, 'delivering');

        $deviceId = (int) $this->asStaff($courier)
            ->postJson('/api/courier/devices', [
                'token' => 'fcm-device-token-1',
                'platform' => 'android',
                'device_name' => 'Samsung A55',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->asStaff($otherCourier)
            ->deleteJson("/api/courier/devices/{$deviceId}")
            ->assertNotFound();
        $this->asStaff($courier)->deleteJson("/api/courier/devices/{$deviceId}")->assertNoContent();
        $this->assertNull(CourierDevice::find($deviceId));

        $this->asStaff($otherCourier)
            ->postJson('/api/courier/support', [
                'order_id' => $order->id,
                'category' => 'customer',
                'message' => 'Begona buyurtma bo‘yicha xabar',
            ])->assertNotFound();

        $ticketId = (int) $this->asStaff($courier)
            ->postJson('/api/courier/support', [
                'order_id' => $order->id,
                'category' => 'customer',
                'message' => 'Mijoz bilan bog‘lanib bo‘lmadi',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->json('data.id');

        $this->asStaff($admin)
            ->getJson('/api/admin/courier-support?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ticketId);

        $this->putJson("/api/admin/courier-support/{$ticketId}", ['status' => 'resolved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reply');

        $this->putJson("/api/admin/courier-support/{$ticketId}", [
            'status' => 'resolved',
            'reply' => 'Mijoz bilan admin bog‘landi.',
        ])->assertOk()->assertJsonPath('data.status', 'resolved');

        $ticket = CourierSupportTicket::findOrFail($ticketId);
        $this->assertSame($admin->id, $ticket->resolved_by);
        $notification = CourierNotification::where('courier_id', $courier->id)
            ->where('type', 'support_updated')
            ->firstOrFail();

        $this->asStaff($otherCourier)
            ->putJson("/api/courier/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->asStaff($courier)
            ->getJson('/api/courier/support')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.admin_reply', 'Mijoz bilan admin bog‘landi.');
    }

    public function test_earnings_and_payouts_are_server_calculated_and_super_admin_only(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'payout');
        $otherCourier = $this->staff(StaffRole::COURIER, 'payout-other');
        $admin = $this->staff(StaffRole::ADMIN, 'payout');
        $superAdmin = $this->staff(StaffRole::SUPER_ADMIN, 'payout');
        $this->order($courier, 'delivered', courierFee: 10000, deliveredAt: now());
        $this->order($courier, 'delivered', courierFee: 15000, deliveredAt: now()->subDay());
        $this->order($otherCourier, 'delivered', courierFee: 99000, deliveredAt: now());

        $this->asStaff($courier)
            ->getJson('/api/courier/earnings')
            ->assertOk()
            ->assertJsonPath('data.today', 10000)
            ->assertJsonPath('data.total_earned', 25000)
            ->assertJsonPath('data.unpaid', 25000);

        $payload = [
            'courier_id' => $courier->id,
            'period_start' => now()->subDays(2)->toDateString(),
            'period_end' => now()->toDateString(),
            'note' => 'Haftalik to‘lov',
        ];

        $this->asStaff($admin)
            ->postJson('/api/super/courier-payouts', $payload)
            ->assertForbidden();

        $payoutId = (int) $this->asStaff($superAdmin)
            ->postJson('/api/super/courier-payouts', $payload)
            ->assertCreated()
            ->assertJsonPath('data.amount', 25000)
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        $this->postJson('/api/super/courier-payouts', $payload)->assertUnprocessable();

        $this->putJson("/api/super/courier-payouts/{$payoutId}/paid")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $payout = CourierPayout::findOrFail($payoutId);
        $this->assertNotNull($payout->paid_at);
        $this->assertDatabaseHas('courier_notifications', [
            'courier_id' => $courier->id,
            'type' => 'payout_paid',
        ]);

        $this->asStaff($courier)
            ->getJson('/api/courier/earnings')
            ->assertOk()
            ->assertJsonPath('data.paid', 25000)
            ->assertJsonPath('data.unpaid', 0);
    }

    public function test_courier_endpoints_require_a_courier_identity(): void
    {
        $manager = $this->staff(StaffRole::MANAGER, 'security');

        $this->getJson('/api/courier/profile')->assertUnauthorized();
        $this->asCustomer()->getJson('/api/courier/profile')->assertUnauthorized();
        $this->asStaff($manager)->getJson('/api/courier/profile')->assertForbidden();
    }
}
