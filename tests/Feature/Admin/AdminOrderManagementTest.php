<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function staff(StaffRole $role, string $suffix, bool $active = true): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role->value,
            'is_active' => $active,
        ]);
    }

    private function as(Staff $staff): static
    {
        return $this->withToken($staff->createToken('test')->plainTextToken);
    }

    private function customer(string $phone, string $name): User
    {
        return User::create(['phone' => $phone, 'name' => $name]);
    }

    private function order(User $user, string $status, string $date = '2026-07-21 10:00:00'): OrderModel
    {
        $order = OrderModel::create([
            'user_id' => $user->id,
            'status' => $status,
            'address' => ['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1'],
            'phone' => $user->phone,
            'delivery_time' => 'Ertaga',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => 10000,
            'grand_total' => 115000,
        ]);

        $order->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();

        return $order->fresh();
    }

    public function test_order_list_filters_by_search_status_and_dates(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'filters');
        $ali = $this->customer('+998901111111', 'Ali Valiyev');
        $vali = $this->customer('+998902222222', 'Vali Aliyev');
        $target = $this->order($ali, 'paid', '2026-07-10 10:00:00');
        $this->order($vali, 'pending', '2026-07-20 10:00:00');

        $this->as($admin)
            ->getJson('/api/dashboard/orders?search=Ali%20Valiyev&status=paid&date_from=2026-07-01&date_to=2026-07-15')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        $this->getJson('/api/dashboard/orders?status=unknown')->assertStatus(422);
        $this->getJson('/api/dashboard/orders?date_from=2026-07-20&date_to=2026-07-10')->assertStatus(422);
    }

    public function test_ready_order_can_be_assigned_only_to_active_courier(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'assign');
        $courier = $this->staff(StaffRole::COURIER, 'active');
        $user = $this->customer('+998903333333', 'Customer');
        $order = $this->order($user, 'ready_to_deliver');

        $this->as($admin)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
        ]);

        $this->getJson("/api/dashboard/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.courier.id', $courier->id)
            ->assertJsonPath('data.courier.name', $courier->name);
    }

    public function test_inactive_courier_and_non_courier_are_rejected(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'invalid-courier');
        $inactive = $this->staff(StaffRole::COURIER, 'inactive', false);
        $manager = $this->staff(StaffRole::MANAGER, 'not-courier');
        $order = $this->order($this->customer('+998904444444', 'Customer'), 'ready_to_deliver');
        $this->as($admin);

        foreach ([$inactive->id, $manager->id, 999999] as $courierId) {
            $this->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courierId,
            ])->assertStatus(422)->assertJsonValidationErrors('courier_id');
        }
    }

    public function test_courier_cannot_be_assigned_before_order_is_ready(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'wrong-status');
        $courier = $this->staff(StaffRole::COURIER, 'ready');
        $order = $this->order($this->customer('+998905555555', 'Customer'), 'confirmed');

        $this->as($admin)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => null]);
    }

    public function test_available_couriers_returns_only_active_couriers(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'available');
        $active = $this->staff(StaffRole::COURIER, 'available', true);
        $inactive = $this->staff(StaffRole::COURIER, 'hidden', false);
        $this->staff(StaffRole::MANAGER, 'hidden');

        $this->as($admin)
            ->getJson('/api/admin/couriers/available')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $active->id])
            ->assertJsonMissing(['id' => $inactive->id]);
    }

    public function test_manager_cannot_assign_courier(): void
    {
        $manager = $this->staff(StaffRole::MANAGER, 'forbidden');
        $courier = $this->staff(StaffRole::COURIER, 'target');
        $order = $this->order($this->customer('+998906666666', 'Customer'), 'ready_to_deliver');

        $this->as($manager)
            ->putJson("/api/admin/orders/{$order->id}/assign-courier", [
                'courier_id' => $courier->id,
            ])->assertStatus(403);
    }
}
