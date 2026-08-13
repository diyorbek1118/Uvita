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

class ChangePhoneTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private string $endpoint = '/api/auth/phone/change';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    private function actingUser(array $attributes = []): User
    {
        $user = User::create(array_merge([
            'phone' => '+998901234567',
            'name'  => 'Ali',
        ], $attributes));

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeaders(['Authorization' => "Bearer {$token}"]);

        return $user;
    }

    private function createVerifiedOtp(string $phone): void
    {
        OtpAttempt::create([
            'phone'          => $phone,
            'code'           => '1234',
            'expires_at'     => now()->addMinutes(15),
            'attempts_count' => 0,
            'is_verified'    => true,
        ]);
    }

    public function test_change_phone_requires_auth(): void
    {
        $response = $this->postJson($this->endpoint, [
            'phone' => '+998909991126',
            'code'  => '1234',
        ]);

        $response->assertStatus(401);
    }

    public function test_change_phone_rejects_unconfirmed_otp(): void
    {
        $this->actingUser();

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998909991126',
            'code'  => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($v) => str_contains($v, 'tasdiqlanmagan'));
    }

    public function test_change_phone_rejects_own_current_phone(): void
    {
        $this->actingUser();

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
            'code'  => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($v) => str_contains($v, 'joriy'));
    }

    public function test_change_phone_rejects_phone_taken_by_another_user(): void
    {
        $this->actingUser();
        User::create(['phone' => '+998909991126', 'name' => 'Botir']);
        $this->createVerifiedOtp('+998909991126');

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998909991126',
            'code'  => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($v) => str_contains($v, 'allaqachon'));
    }

    public function test_change_phone_requires_current_password_when_set(): void
    {
        $this->actingUser(['password' => Hash::make('secret123')]);
        $this->createVerifiedOtp('+998909991126');

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998909991126',
            'code'  => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($v) => str_contains($v, 'parol'));
    }

    public function test_change_phone_accepts_correct_current_password(): void
    {
        $user = $this->actingUser(['password' => Hash::make('secret123')]);
        $this->createVerifiedOtp('+998909991126');

        $response = $this->postJson($this->endpoint, [
            'phone'            => '+998909991126',
            'code'             => '1234',
            'current_password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.phone', '+998909991126');

        $user->refresh();
        $this->assertSame('+998909991126', $user->phone);
    }

    public function test_change_phone_updates_phone_after_sms_confirmation(): void
    {
        $user = $this->actingUser();
        $this->createVerifiedOtp('+998909991126');

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998909991126',
            'code'  => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.phone', '+998909991126');

        $user->refresh();
        $this->assertSame('+998909991126', $user->phone);
        $this->assertSame('Ali', $user->name); // boshqa maydon o'zgarmadi
    }

    // ── /auth/otp/send purpose=change_phone ──

    public function test_send_otp_change_phone_rejects_registered_number(): void
    {
        User::create(['phone' => '+998909991126', 'name' => 'Botir']);

        $response = $this->postJson('/api/auth/otp/send', [
            'phone'   => '+998909991126',
            'purpose' => 'change_phone',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_otp_change_phone_sends_for_free_number(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/auth/otp/send', [
            'phone'   => '+998909991126',
            'purpose' => 'change_phone',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'SMS yuborildi');

        Queue::assertPushed(\App\Jobs\SendSmsJob::class);
    }

    // ── /auth/otp/confirm purpose=change_phone ──

    public function test_confirm_otp_change_phone_rejects_registered_number(): void
    {
        User::create(['phone' => '+998909991126', 'name' => 'Botir']);
        OtpAttempt::create([
            'phone'          => '+998909991126',
            'code'           => '1234',
            'expires_at'     => now()->addMinutes(2),
            'attempts_count' => 0,
            'is_verified'    => false,
        ]);

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998909991126',
            'code'    => '1234',
            'purpose' => 'change_phone',
        ]);

        $response->assertStatus(422);
    }

    public function test_confirm_otp_change_phone_marks_verified(): void
    {
        OtpAttempt::create([
            'phone'          => '+998909991126',
            'code'           => '1234',
            'expires_at'     => now()->addMinutes(2),
            'attempts_count' => 0,
            'is_verified'    => false,
        ]);

        $response = $this->postJson('/api/auth/otp/confirm', [
            'phone'   => '+998909991126',
            'code'    => '1234',
            'purpose' => 'change_phone',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('otp_attempts', [
            'phone'       => '+998909991126',
            'is_verified' => true,
        ]);
    }
}
