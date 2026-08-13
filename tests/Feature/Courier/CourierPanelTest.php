<?php

declare(strict_types=1);

namespace Tests\Feature\Courier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

final class CourierPanelTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();
        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Test Customer']);
    }

    private function staff(StaffRole $role, string $suffix, bool $active = true): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function as(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('courier-test')->plainTextToken);
    }

    private function deliveryPin(OrderModel $order): string
    {
        $service = app(DeliveryConfirmationService::class);

        return $service->reveal($service->ensureForOrder($order->id));
    }

    private function order(Staff $courier, string $status, int $notFoundCount = 0): OrderModel
    {
        return OrderModel::create([
            'user_id' => $this->customer->id,
            'courier_id' => $courier->id,
            'status' => $status,
            'address' => ['region' => 'Toshkent', 'district' => 'Yunusobod', 'street' => 'Navoiy', 'house' => '1'],
            'phone' => $this->customer->phone,
            'phone_secondary' => '+998909999999',
            'delivery_time' => 'Bugun 14:00',
            'courier_note' => '2 ta paket',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => 10000,
            'grand_total' => 115000,
            'not_found_count' => $notFoundCount,
        ]);
    }

    public function test_courier_profile_and_permissions_are_scoped(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'profile');

        $this->as($courier)
            ->getJson('/api/courier/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', $courier->name)
            ->assertJsonPath('data.role', 'courier')
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/dashboard/products')->assertForbidden();
        $this->getJson('/api/admin/couriers')->assertForbidden();
        $this->getJson('/api/super/settings')->assertForbidden();
    }

    public function test_inactive_courier_is_blocked_even_with_existing_token(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'inactive', false);

        $this->as($courier)
            ->getJson('/api/courier/profile')
            ->assertForbidden();
    }

    public function test_active_orders_show_only_assigned_ready_and_delivering_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'owner');
        $other = $this->staff(StaffRole::COURIER, 'other');
        $ready = $this->order($courier, 'ready_to_deliver');
        $delivering = $this->order($courier, 'delivering');
        $delivered = $this->order($courier, 'delivered');
        $foreign = $this->order($other, 'ready_to_deliver');

        $this->as($courier)
            ->getJson('/api/courier/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $ready->id])
            ->assertJsonFragment(['id' => $delivering->id])
            ->assertJsonMissing(['id' => $delivered->id])
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->getJson("/api/courier/orders/{$ready->id}")->assertOk();
        $this->getJson("/api/courier/orders/{$foreign->id}")->assertNotFound();
    }

    public function test_history_contains_only_own_delivered_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'history');
        $other = $this->staff(StaffRole::COURIER, 'history-other');
        $own = $this->order($courier, 'delivered');
        $this->order($courier, 'delivering');
        $foreign = $this->order($other, 'delivered');
        $own->forceFill(['delivered_at' => now()])->saveQuietly();

        $this->as($courier)
            ->getJson('/api/courier/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.delivered_at', $own->fresh()->delivered_at?->toISOString())
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_only_assigned_courier_can_accept_ready_order(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'accept-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'accept-attacker');
        $order = $this->order($owner, 'ready_to_deliver');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => $owner->id, 'status' => 'ready_to_deliver']);

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering');

        $updated = $order->fresh();
        $this->assertSame($owner->id, $updated->courier_id);
        $this->assertNotNull($updated->delivering_at);
    }

    public function test_accept_rejects_wrong_status_including_delivery_issue(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'accept-status');

        foreach (['confirmed', 'delivering', 'delivered', 'delivery_issue'] as $status) {
            $order = $this->order($courier, $status);
            $this->as($courier)
                ->putJson("/api/courier/orders/{$order->id}/accept")
                ->assertUnprocessable();
        }
    }

    public function test_only_assigned_courier_can_mark_delivered(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'deliver-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'deliver-attacker');
        $order = $this->order($owner, 'delivering');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/delivered")
            ->assertNotFound();

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/delivered", [
                'pin' => $this->deliveryPin($order),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertNotNull($order->fresh()->delivered_at);
        $this->getJson('/api/courier/orders')->assertJsonCount(0, 'data');
        $this->getJson('/api/courier/history')->assertJsonFragment(['id' => $order->id]);
    }

    public function test_delivered_action_rejects_non_delivering_status(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'deliver-status');
        $order = $this->order($courier, 'ready_to_deliver');

        $this->as($courier)
            ->putJson("/api/courier/orders/{$order->id}/delivered")
            ->assertUnprocessable();
    }

    public function test_not_found_requires_reason_and_only_owner_can_increment(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'not-found-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'not-found-attacker');
        $order = $this->order($owner, 'delivering');

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/not-found", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Telefonni olmadi'])
            ->assertNotFound();

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Telefonni olmadi'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering')
            ->assertJsonPath('data.not_found_count', 1);
    }

    public function test_third_not_found_attempt_creates_delivery_issue_and_stops_more_attempts(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'third-attempt');
        $order = $this->order($courier, 'delivering', 2);

        $this->as($courier)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Uchinchi urinish'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivery_issue')
            ->assertJsonPath('data.not_found_count', 3);

        $updated = $order->fresh();
        $this->assertNotNull($updated->delivery_issue_at);

        $this->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'To‘rtinchi urinish'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'not_found_count' => 3, 'status' => 'delivery_issue']);
        $this->getJson('/api/courier/orders')->assertJsonCount(0, 'data');
    }

    public function test_stats_use_only_current_courier_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'stats');
        $other = $this->staff(StaffRole::COURIER, 'stats-other');
        $this->order($courier, 'delivered', 1);
        $this->order($courier, 'delivered', 2);
        $this->order($courier, 'ready_to_deliver');
        $this->order($courier, 'delivering');
        $this->order($other, 'delivered', 10);

        $this->as($courier)
            ->getJson('/api/courier/stats')
            ->assertOk()
            ->assertJsonPath('data.total_delivered', 2)
            ->assertJsonPath('data.total_not_found', 3)
            ->assertJsonPath('data.total_active', 2)
            ->assertJsonPath('data.success_rate', 40);
    }
}
