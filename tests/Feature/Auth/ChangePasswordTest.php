<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private string $endpoint = '/api/auth/password/change';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    private function actingUser(array $attributes = []): User
    {
        $user = User::create(array_merge([
            'phone'    => '+998901234567',
            'name'     => 'Ali',
            'password' => Hash::make('oldpass123'),
        ], $attributes));

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeaders(['Authorization' => "Bearer {$token}"]);

        return $user;
    }

    public function test_change_password_updates_password(): void
    {
        $user = $this->actingUser();

        $response = $this->postJson($this->endpoint, [
            'current_password' => 'oldpass123',
            'new_password'     => 'newpass456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Parol yangilandi');

        $user->refresh();
        $this->assertTrue(Hash::check('newpass456', $user->password));
        $this->assertFalse(Hash::check('oldpass123', $user->password));
    }

    public function test_change_password_rejects_wrong_current(): void
    {
        $this->actingUser();

        $response = $this->postJson($this->endpoint, [
            'current_password' => 'wrongpass',
            'new_password'     => 'newpass456',
        ]);

        $response->assertStatus(422);
    }

    public function test_change_password_for_passwordless_user_sets_new(): void
    {
        $user = $this->actingUser(['password' => null]);

        $response = $this->postJson($this->endpoint, [
            'new_password' => 'newpass456',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('newpass456', $user->password));
    }

    public function test_change_password_requires_short_new_rejected(): void
    {
        $this->actingUser();

        $response = $this->postJson($this->endpoint, [
            'current_password' => 'oldpass123',
            'new_password'     => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.new_password', fn ($v) => !empty($v));
    }

    public function test_change_password_requires_auth(): void
    {
        $response = $this->postJson($this->endpoint, [
            'current_password' => 'oldpass123',
            'new_password'     => 'newpass456',
        ]);

        $response->assertStatus(401);
    }
}
