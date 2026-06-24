<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Auth;

use DateTimeImmutable;
use Modules\Auth\Domain\Entities\OtpAttempt;
use PHPUnit\Framework\TestCase;

class OtpAttemptTest extends TestCase
{
    private function makeAttempt(
        string            $code        = '123456',
        int               $ttlSeconds  = 120,
        int               $attempts    = 0,
        ?DateTimeImmutable $blockedUntil = null,
        bool              $isVerified  = false,
    ): OtpAttempt {
        return new OtpAttempt(
            id:            null,
            phone:         '+998901234567',
            code:          $code,
            attemptsCount: $attempts,
            blockedUntil:  $blockedUntil,
            expiresAt:     new DateTimeImmutable("+{$ttlSeconds} seconds"),
            isVerified:    $isVerified,
        );
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_create_initializes_defaults(): void
    {
        $attempt = OtpAttempt::create(
            phone:     '+998901234567',
            code:      '654321',
            expiresAt: new DateTimeImmutable('+120 seconds'),
        );

        $this->assertNull($attempt->id);
        $this->assertSame('+998901234567', $attempt->phone);
        $this->assertSame('654321', $attempt->code);
        $this->assertSame(0, $attempt->attemptsCount);
        $this->assertNull($attempt->blockedUntil);
        $this->assertFalse($attempt->isVerified);
    }

    // ─── isExpired ────────────────────────────────────────────────────────────

    public function test_not_expired_when_expiry_is_in_future(): void
    {
        $attempt = $this->makeAttempt(ttlSeconds: 120);

        $this->assertFalse($attempt->isExpired());
    }

    public function test_expired_when_expiry_is_in_past(): void
    {
        $attempt = new OtpAttempt(
            id:            null,
            phone:         '+998901234567',
            code:          '111111',
            attemptsCount: 0,
            blockedUntil:  null,
            expiresAt:     new DateTimeImmutable('-1 second'),
            isVerified:    false,
        );

        $this->assertTrue($attempt->isExpired());
    }

    // ─── isBlocked ────────────────────────────────────────────────────────────

    public function test_not_blocked_when_blocked_until_is_null(): void
    {
        $attempt = $this->makeAttempt();

        $this->assertFalse($attempt->isBlocked());
    }

    public function test_blocked_when_blocked_until_is_in_future(): void
    {
        $attempt = $this->makeAttempt(blockedUntil: new DateTimeImmutable('+10 minutes'));

        $this->assertTrue($attempt->isBlocked());
    }

    public function test_not_blocked_when_blocked_until_is_in_past(): void
    {
        $attempt = $this->makeAttempt(blockedUntil: new DateTimeImmutable('-1 second'));

        $this->assertFalse($attempt->isBlocked());
    }

    // ─── isValid ──────────────────────────────────────────────────────────────

    public function test_valid_when_code_matches_and_not_expired_or_blocked(): void
    {
        $attempt = $this->makeAttempt(code: '123456');

        $this->assertTrue($attempt->isValid('123456'));
    }

    public function test_invalid_when_code_does_not_match(): void
    {
        $attempt = $this->makeAttempt(code: '123456');

        $this->assertFalse($attempt->isValid('999999'));
    }

    public function test_invalid_when_expired(): void
    {
        $attempt = new OtpAttempt(
            id:            null,
            phone:         '+998901234567',
            code:          '123456',
            attemptsCount: 0,
            blockedUntil:  null,
            expiresAt:     new DateTimeImmutable('-1 second'),
            isVerified:    false,
        );

        $this->assertFalse($attempt->isValid('123456'));
    }

    public function test_invalid_when_blocked(): void
    {
        $attempt = $this->makeAttempt(
            code:         '123456',
            blockedUntil: new DateTimeImmutable('+10 minutes'),
        );

        $this->assertFalse($attempt->isValid('123456'));
    }

    public function test_invalid_when_already_verified(): void
    {
        $attempt = $this->makeAttempt(code: '123456', isVerified: true);

        $this->assertFalse($attempt->isValid('123456'));
    }

    // ─── incrementAttempts ────────────────────────────────────────────────────

    public function test_increment_increases_attempts_count(): void
    {
        $attempt = $this->makeAttempt(attempts: 0);
        $attempt->incrementAttempts(5, 10);

        $this->assertSame(1, $attempt->attemptsCount);
    }

    public function test_no_block_before_max_attempts(): void
    {
        $attempt = $this->makeAttempt(attempts: 3);
        $attempt->incrementAttempts(5, 10);

        $this->assertNull($attempt->blockedUntil);
    }

    public function test_block_set_at_max_attempts(): void
    {
        $attempt = $this->makeAttempt(attempts: 4);
        $attempt->incrementAttempts(5, 10);

        $this->assertSame(5, $attempt->attemptsCount);
        $this->assertNotNull($attempt->blockedUntil);
        $this->assertTrue($attempt->isBlocked());
    }

    // ─── markAsVerified ───────────────────────────────────────────────────────

    public function test_mark_as_verified_sets_flag(): void
    {
        $attempt = $this->makeAttempt();
        $attempt->markAsVerified();

        $this->assertTrue($attempt->isVerified);
    }

    public function test_mark_as_verified_makes_code_invalid(): void
    {
        $attempt = $this->makeAttempt(code: '123456');
        $attempt->markAsVerified();

        $this->assertFalse($attempt->isValid('123456'));
    }
}
