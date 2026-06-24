<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Infrastructure\Persistence\Models\OtpAttempt;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class SendOtpTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private string $endpoint = '/api/auth/otp/send';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    public function test_send_otp_creates_attempt_and_returns_200(): void
    {
        Queue::fake();

        $response = $this->postJson($this->endpoint, [
            'phone' => '+998901234567',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('otp_attempts', ['phone' => '+998901234567']);
    }

    public function test_send_otp_dispatches_sms_job(): void
    {
        Queue::fake();

        $this->postJson($this->endpoint, ['phone' => '+998901234567']);

        Queue::assertPushed(\App\Jobs\SendSmsJob::class);
    }

    public function test_send_otp_requires_phone(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.phone', fn($v) => !empty($v));
    }

    public function test_send_otp_rejects_invalid_phone_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'phone' => '998901234567',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_otp_does_not_resend_when_active_otp_exists(): void
    {
        Queue::fake();

        OtpAttempt::create([
            'phone'          => '+998901234567',
            'code'           => '123456',
            'expires_at'     => now()->addSeconds(120),
            'attempts_count' => 0,
            'is_verified'    => false,
        ]);

        $this->postJson($this->endpoint, ['phone' => '+998901234567']);

        // Faqat bitta OTP bo'lishi kerak
        $this->assertDatabaseCount('otp_attempts', 1);
        Queue::assertNothingPushed();
    }

    public function test_send_otp_blocked_returns_429(): void
    {
        Queue::fake();

        OtpAttempt::create([
            'phone'          => '+998901234567',
            'code'           => '123456',
            'expires_at'     => now()->addSeconds(120),
            'attempts_count' => 5,
            'blocked_until'  => now()->addMinutes(10),
            'is_verified'    => false,
        ]);

        $response = $this->postJson($this->endpoint, ['phone' => '+998901234567']);

        $response->assertStatus(429);
    }
}
