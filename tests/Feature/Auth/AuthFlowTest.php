<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Infrastructure\Persistence\Models\OtpAttempt;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    private function createOtp(array $attributes = []): void
    {
        OtpAttempt::create(array_merge([
            'phone'          => '+998901234567',
            'code'           => '1234',
            'expires_at'     => now()->addSeconds(120),
            'attempts_count' => 0,
            'is_verified'    => false,
        ], $attributes));
    }

    private function createVerifiedOtp(string $phone = '+998901234567'): void
    {
        $this->createOtp(['phone' => $phone, 'is_verified' => true]);
    }

    private function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'phone' => '+998901234567',
            'name'  => 'Ali',
        ], $attributes));
    }

    // ── /auth/check ──

    public function test_check_returns_not_registered_for_new_phone(): void
    {
        $response = $this->postJson('/api/auth/check', ['phone' => '+998901234567']);

        $response->assertStatus(200)
            ->assertJsonPath('data.registered', false)
            ->assertJsonPath('data.has_password', false);
    }

    public function test_check_returns_registered_without_password(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/check', ['phone' => '+998901234567']);

        $response->assertStatus(200)
            ->assertJsonPath('data.registered', true)
            ->assertJsonPath('data.has_password', false);
    }

    public function test_check_returns_has_password_true(): void
    {
        $this->createUser(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/auth/check', ['phone' => '+998901234567']);

        $response->assertStatus(200)
            ->assertJsonPath('data.registered', true)
            ->assertJsonPath('data.has_password', true);
    }

    // ── /auth/otp/send purpose ──

    public function test_send_otp_register_rejects_existing_phone(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/otp/send', [
            'phone'   => '+998901234567',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_otp_reset_rejects_new_phone(): void
    {
        $response = $this->postJson('/api/auth/otp/send', [
            'phone'   => '+998901234567',
            'purpose' => 'reset',
        ]);

        $response->assertStatus(422);
    }

    // ── /auth/otp/confirm ──

    public function test_confirm_otp_marks_code_verified(): void
    {
        $this->createOtp();

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998901234567',
            'code'    => '1234',
            'purpose' => 'register',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kod tasdiqlandi');

        $this->assertDatabaseHas('otp_attempts', [
            'phone'       => '+998901234567',
            'is_verified' => true,
        ]);
    }

    public function test_confirm_otp_extends_expiry_for_remaining_steps(): void
    {
        $this->createOtp(['expires_at' => now()->addSeconds(120)]);

        $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998901234567',
            'code'    => '1234',
            'purpose' => 'register',
        ])->assertStatus(200);

        // Tasdiqlangach muddat uzaytiriladi — qolgan bosqichlar uchun yetarli vaqt
        $this->assertDatabaseHas('otp_attempts', [
            'phone'       => '+998901234567',
            'is_verified' => true,
        ]);
        $attempt = OtpAttempt::where('phone', '+998901234567')->first();
        $this->assertTrue($attempt->expires_at->greaterThan(now()->addMinutes(10)));
    }

    public function test_confirm_otp_wrong_code_returns_422(): void
    {
        $this->createOtp(['code' => '1234']);

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998901234567',
            'code'    => '9999',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
    }

    public function test_confirm_otp_register_rejects_existing_phone(): void
    {
        $this->createUser();
        $this->createOtp();

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998901234567',
            'code'    => '1234',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
    }

    public function test_confirm_otp_reset_rejects_new_phone(): void
    {
        $this->createOtp();

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998901234567',
            'code'    => '1234',
            'purpose' => 'reset',
        ]);

        $response->assertStatus(422);
    }

    // ── /auth/register ──

    public function test_register_stores_lat_lng(): void
    {
        Queue::fake();
        $this->createVerifiedOtp();

        $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'name'     => 'Azamat',
            'region'   => 'Toshkent',
            'address'  => 'Chilonzor 12',
            'lat'      => 41.3111587,
            'lng'      => 69.2797371,
            'password' => 'secret123',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'phone' => '+998901234567',
            'lat'   => 41.3111587,
            'lng'   => 69.2797371,
        ]);
    }

    public function test_register_creates_user_with_profile_and_password(): void
    {
        Queue::fake();
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'name'     => 'Azamat',
            'surname'  => 'Fermerov',
            'region'   => 'Toshkent',
            'district' => 'Yunusobod',
            'address'  => 'Amir Temur ko\'chasi, 107B',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('users', [
            'phone'    => '+998901234567',
            'name'     => 'Azamat',
            'surname'  => 'Fermerov',
            'region'   => 'Toshkent',
            'address'  => 'Amir Temur ko\'chasi, 107B',
        ]);

        $user = User::where('phone', '+998901234567')->first();
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_register_requires_confirmed_otp(): void
    {
        // Tasdiqlanmagan (is_verified=false) OTP bilan register ishlamaydi
        $this->createOtp();

        $response = $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'name'     => 'Azamat',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_existing_phone(): void
    {
        $this->createUser(['password' => Hash::make('secret123')]);
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'name'     => 'Azamat',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_short_password_rejected(): void
    {
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'name'     => 'Azamat',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password', fn ($v) => !empty($v));
    }

    public function test_register_works_without_code_when_otp_confirmed(): void
    {
        // Refresh'dan keyin kod sessionStorage'da bo'lmasa ham register ishlashi kerak —
        // muhimi OTP avval /otp/confirm orqali tasdiqlangan bo'lishi (is_verified=true)
        Queue::fake();
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/register', [
            'phone'    => '+998901234567',
            'name'     => 'Azamat',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('users', ['phone' => '+998901234567']);
    }

    // ── /auth/login ──

    public function test_login_with_correct_password_returns_token(): void
    {
        $this->createUser(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'phone'    => '+998901234567',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        $this->createUser(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'phone'    => '+998901234567',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_without_password_set_returns_422_with_guidance(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'phone'    => '+998901234567',
            'password' => 'anything',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($v) => str_contains($v, 'parol'));
    }

    public function test_login_for_unknown_phone_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'phone'    => '+998901234567',
            'password' => 'anything',
        ]);

        $response->assertStatus(422);
    }

    // ── /auth/password/reset ──

    public function test_reset_password_sets_new_password_and_returns_token(): void
    {
        $user = $this->createUser(['password' => Hash::make('oldpass123')]);
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/password/reset', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'password' => 'newpass456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpass456', $user->password));
        $this->assertFalse(Hash::check('oldpass123', $user->password));
    }

    public function test_reset_password_requires_confirmed_otp(): void
    {
        $this->createUser(['password' => Hash::make('oldpass123')]);
        $this->createOtp(); // tasdiqlanmagan

        $response = $this->postJson('/api/auth/password/reset', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'password' => 'newpass456',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_for_unknown_phone_returns_422(): void
    {
        $this->createVerifiedOtp();

        $response = $this->postJson('/api/auth/password/reset', [
            'phone'    => '+998901234567',
            'code'     => '1234',
            'password' => 'newpass456',
        ]);

        $response->assertStatus(422);
    }
}
