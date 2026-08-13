<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(StaffRole $role, string $suffix, bool $active = true): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role->value,
            'is_active' => $active,
        ]);
    }

    private function asStaff(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('test')->plainTextToken);
    }

    private function withFreshToken(string $token): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($token);
    }

    private function order(?int $courierId = null): OrderModel
    {
        $user = User::create([
            'phone' => '+99890'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT),
            'name' => 'Customer',
        ]);

        return OrderModel::create([
            'user_id' => $user->id,
            'courier_id' => $courierId,
            'status' => 'ready_to_deliver',
            'address' => ['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1'],
            'phone' => $user->phone,
            'delivery_time' => 'Ertaga',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => 10000,
            'grand_total' => 115000,
        ]);
    }

    public function test_admin_can_create_manager_and_courier_and_they_can_login(): void
    {
        $admin = $this->createStaff(StaffRole::ADMIN, 'creator');
        $this->asStaff($admin);

        foreach ([StaffRole::MANAGER, StaffRole::COURIER] as $role) {
            $email = "new-{$role->value}@uvita.uz";

            $this->postJson('/api/dashboard/staff', [
                'name' => "New {$role->value}",
                'email' => $email,
                'password' => 'password123',
                'role' => $role->value,
            ])->assertStatus(201)->assertJsonPath('data.role', $role->value);

            $this->postJson('/api/staff/login', [
                'email' => $email,
                'password' => 'password123',
            ])->assertStatus(200)->assertJsonPath('data.staff.role', $role->value);
        }
    }

    public function test_admin_cannot_create_or_manage_privileged_staff(): void
    {
        $admin = $this->createStaff(StaffRole::ADMIN, 'limited');
        $otherAdmin = $this->createStaff(StaffRole::ADMIN, 'other');
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'root');
        $this->asStaff($admin);

        $this->postJson('/api/dashboard/staff', [
            'name' => 'Forbidden Admin',
            'email' => 'forbidden-admin@uvita.uz',
            'password' => 'password123',
            'role' => 'admin',
        ])->assertStatus(403);

        $this->getJson('/api/dashboard/staff')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $otherAdmin->id])
            ->assertJsonMissing(['id' => $super->id]);

        $this->getJson("/api/dashboard/staff/{$super->id}")->assertStatus(403);
    }

    public function test_role_change_updates_permissions_for_existing_token(): void
    {
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'role-editor');
        $staff = $this->createStaff(StaffRole::MANAGER, 'switch');
        $staffToken = $staff->createToken('existing')->plainTextToken;
        $this->asStaff($super);

        $this->putJson("/api/super/staff/{$staff->id}", [
            'name' => 'Now Courier',
            'email' => 'now-courier@uvita.uz',
            'role' => 'courier',
        ])->assertStatus(200)->assertJsonPath('data.role', 'courier');

        $this->withFreshToken($staffToken)->getJson('/api/courier/profile')->assertStatus(200);
        $this->getJson('/api/dashboard/products')->assertStatus(403);
    }

    public function test_deactivation_blocks_existing_token_and_new_login(): void
    {
        $admin = $this->createStaff(StaffRole::ADMIN, 'blocker');
        $manager = $this->createStaff(StaffRole::MANAGER, 'blocked');
        $managerToken = $manager->createToken('existing')->plainTextToken;
        $this->asStaff($admin)
            ->putJson("/api/dashboard/staff/{$manager->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->withFreshToken($managerToken)->getJson('/api/dashboard/products')->assertStatus(403);
        $this->postJson('/api/staff/login', [
            'email' => $manager->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_staff_role_filter_rejects_unknown_role(): void
    {
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'filter');
        $this->asStaff($super)
            ->getJson('/api/super/staff?role=owner')
            ->assertStatus(422);
    }

    public function test_deleting_manager_preserves_product_and_revokes_tokens(): void
    {
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'delete-manager');
        $manager = $this->createStaff(StaffRole::MANAGER, 'deleted');
        $manager->createToken('to-delete');
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'name' => 'Managed',
            'slug' => 'managed',
            'description' => 'D',
            'price' => 10000,
            'stock' => 1,
            'status' => 'inactive',
            'images' => [],
            'category_id' => $category->id,
            'manager_id' => $manager->id,
        ]);

        $this->asStaff($super)
            ->deleteJson("/api/super/staff/{$manager->id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'manager_id' => null]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => Staff::class,
            'tokenable_id' => $manager->id,
        ]);
    }

    public function test_deleting_courier_preserves_order_and_detaches_assignment(): void
    {
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'delete-courier');
        $courier = $this->createStaff(StaffRole::COURIER, 'deleted');
        $order = $this->order($courier->id);

        $this->asStaff($super)
            ->deleteJson("/api/super/staff/{$courier->id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => null]);
    }

    public function test_super_admin_cannot_be_deleted_or_deactivated(): void
    {
        $super = $this->createStaff(StaffRole::SUPER_ADMIN, 'protected');
        $this->asStaff($super);

        $this->deleteJson("/api/super/staff/{$super->id}")->assertStatus(422);
        $this->putJson("/api/super/staff/{$super->id}/toggle-active")->assertStatus(422);
    }
}
