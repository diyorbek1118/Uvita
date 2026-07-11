<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Tests\TestCase;

class SuperAdminStaffCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): Staff
    {
        return Staff::create([
            'name'      => 'Super Admin',
            'email'     => 'super@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => StaffRole::SUPER_ADMIN->value,
            'is_active' => true,
        ]);
    }

    private function asSuperAdmin(): static
    {
        $token = $this->createSuperAdmin()->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    // ─── POST /api/super/staff ────────────────────────────────────────────────

    public function test_super_admin_can_create_staff(): void
    {
        $response = $this->asSuperAdmin()->postJson('/api/super/staff', [
            'name'     => 'Yangi Manager',
            'email'    => 'manager@uvita.uz',
            'password' => 'password123',
            'role'     => 'manager',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'manager@uvita.uz')
            ->assertJsonPath('data.role', 'manager');
    }

    public function test_create_staff_requires_all_fields(): void
    {
        $response = $this->asSuperAdmin()->postJson('/api/super/staff', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name', fn($v) => !empty($v))
            ->assertJsonPath('errors.email', fn($v) => !empty($v))
            ->assertJsonPath('errors.password', fn($v) => !empty($v))
            ->assertJsonPath('errors.role', fn($v) => !empty($v));
    }

    public function test_create_staff_with_duplicate_email_returns_422(): void
    {
        Staff::create([
            'name'      => 'Existing',
            'email'     => 'existing@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'is_active' => true,
        ]);

        $response = $this->asSuperAdmin()->postJson('/api/super/staff', [
            'name'     => 'Another',
            'email'    => 'existing@uvita.uz',
            'password' => 'password123',
            'role'     => 'manager',
        ]);

        $response->assertStatus(422);
    }

    // ─── GET /api/super/staff ─────────────────────────────────────────────────

    public function test_super_admin_can_list_staff(): void
    {
        Staff::create([
            'name'      => 'Manager',
            'email'     => 'manager@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'is_active' => true,
        ]);

        $response = $this->asSuperAdmin()->getJson('/api/super/staff');

        // Super admin + manager = 2
        $response->assertStatus(200);
    }

    // ─── PUT /api/super/staff/{id} ────────────────────────────────────────────

    public function test_super_admin_can_update_staff(): void
    {
        $staff = Staff::create([
            'name'      => 'Manager',
            'email'     => 'manager@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'is_active' => true,
        ]);

        $response = $this->asSuperAdmin()->putJson("/api/super/staff/{$staff->id}", [
            'name'  => 'Updated Name',
            'email' => 'updated@uvita.uz',
            'role'  => 'courier',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    // ─── DELETE /api/super/staff/{id} ─────────────────────────────────────────

    public function test_super_admin_can_delete_staff(): void
    {
        $staff = Staff::create([
            'name'      => 'Manager',
            'email'     => 'manager@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'is_active' => true,
        ]);

        $response = $this->asSuperAdmin()->deleteJson("/api/super/staff/{$staff->id}");

        $response->assertStatus(204);   // DELETE → 204 No Content (CLAUDE.md konvensiyasi)
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    // ─── PUT /api/super/staff/{id}/toggle-active ─────────────────────────────

    public function test_super_admin_can_toggle_staff_active(): void
    {
        $staff = Staff::create([
            'name'      => 'Manager',
            'email'     => 'manager@uvita.uz',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'is_active' => true,
        ]);

        $response = $this->asSuperAdmin()->putJson("/api/super/staff/{$staff->id}/toggle-active");

        $response->assertStatus(200);
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'is_active' => false]);
    }
}
