<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Infrastructure\Persistence\Models\OtpAttempt;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private string $endpoint = '/api/auth/otp/verify';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    private function createValidOtp(string $phone = '+998901234567', string $code = '123456'): void
    {
        OtpAttempt::create([
            'phone'          => $phone,
            'code'           => $code,
            'expires_at'     => now()->addSeconds(120),
            'attempts_count' => 0,
            'is_verified'    => false,
        ]);
    }

    public function test_verify_correct_otp_returns_token(): void
    {
        Queue::fake();
        $this->createValidOtp(phone: '+998901234567', code: '654321');

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '654321',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user'], 'isNew']);
    }

    public function test_verify_creates_new_user_when_not_exists(): void
    {
        Queue::fake();
        $this->createValidOtp();

        $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $this->assertDatabaseHas('users', ['phone' => '+998901234567']);
    }

    public function test_verify_returns_is_new_true_for_new_user(): void
    {
        Queue::fake();
        $this->createValidOtp();

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('isNew', true);
    }

    public function test_verify_returns_is_new_false_for_existing_user(): void
    {
        Queue::fake();

        User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $this->createValidOtp();

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('isNew', false);
    }

    public function test_wrong_code_returns_422(): void
    {
        $this->createValidOtp(code: '123456');

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '999999',
        ]);

        $response->assertStatus(422);
    }

    public function test_no_active_otp_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_expired_otp_returns_422(): void
    {
        OtpAttempt::create([
            'phone'          => '+998901234567',
            'code'           => '123456',
            'expires_at'     => now()->subSeconds(1),
            'attempts_count' => 0,
            'is_verified'    => false,
        ]);

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_blocked_user_returns_429(): void
    {
        OtpAttempt::create([
            'phone'          => '+998901234567',
            'code'           => '123456',
            'expires_at'     => now()->addSeconds(120),
            'attempts_count' => 5,
            'blocked_until'  => now()->addMinutes(10),
            'is_verified'    => false,
        ]);

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '123456',
        ]);

        $response->assertStatus(429);
    }

    public function test_wrong_code_increments_attempts(): void
    {
        $this->createValidOtp(code: '123456');

        $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '000000',
        ]);

        $this->assertDatabaseHas('otp_attempts', [
            'phone'          => '+998901234567',
            'attempts_count' => 1,
        ]);
    }

    public function test_requires_phone_and_code(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.phone', fn($v) => !empty($v))
            ->assertJsonPath('errors.code', fn($v) => !empty($v));
    }
}
