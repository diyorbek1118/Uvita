<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Tests\TestCase;

class StaffAuthTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(StaffRole $role = StaffRole::ADMIN, bool $isActive = true): Staff
    {
        return Staff::create([
            'name'      => 'Test Staff',
            'email'     => 'test@uvita.uz',
            'password'  => Hash::make('password123'),
            'role'      => $role->value,
            'is_active' => $isActive,
        ]);
    }

    // ─── POST /api/staff/login ────────────────────────────────────────────────

    public function test_staff_can_login_with_correct_credentials(): void
    {
        $this->createStaff();

        $response = $this->postJson('/api/staff/login', [
            'email'    => 'test@uvita.uz',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->createStaff();

        $response = $this->postJson('/api/staff/login', [
            'email'    => 'test@uvita.uz',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email'    => 'nobody@uvita.uz',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_inactive_staff_cannot_login(): void
    {
        $this->createStaff(isActive: false);

        $response = $this->postJson('/api/staff/login', [
            'email'    => 'test@uvita.uz',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/staff/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email', fn($v) => !empty($v))
            ->assertJsonPath('errors.password', fn($v) => !empty($v));
    }

    // ─── POST /api/staff/logout ───────────────────────────────────────────────

    public function test_staff_can_logout(): void
    {
        $staff = $this->createStaff();
        $token = $staff->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/staff/logout');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_logout_returns_401(): void
    {
        $response = $this->postJson('/api/staff/logout');

        $response->assertStatus(401);
    }

    // ─── Role-based access ────────────────────────────────────────────────────

    public function test_manager_cannot_access_admin_endpoints(): void
    {
        $manager = Staff::create([
            'name'      => 'Manager',
            'email'     => 'manager@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => StaffRole::MANAGER->value,
            'is_active' => true,
        ]);
        $token = $manager->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/admin/products/pending');

        $response->assertStatus(403);
    }

    public function test_courier_cannot_access_admin_endpoints(): void
    {
        $courier = Staff::create([
            'name'      => 'Courier',
            'email'     => 'courier@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => StaffRole::COURIER->value,
            'is_active' => true,
        ]);
        $token = $courier->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/admin/products/pending');

        $response->assertStatus(403);
    }

    public function test_admin_cannot_access_super_admin_endpoints(): void
    {
        $admin = $this->createStaff(StaffRole::ADMIN);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/super/staff');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_endpoints(): void
    {
        $superAdmin = Staff::create([
            'name'      => 'Super Admin',
            'email'     => 'super@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => StaffRole::SUPER_ADMIN->value,
            'is_active' => true,
        ]);
        $token = $superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/admin/products/pending');

        $response->assertStatus(200);
    }
}
