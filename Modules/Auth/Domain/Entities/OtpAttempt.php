<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use DateTimeImmutable;

final class OtpAttempt
{
    public function __construct(
        public readonly ?int               $id,
        public readonly string             $phone,
        public readonly string             $code,
        public private(set) int            $attemptsCount,
        public private(set) ?DateTimeImmutable $blockedUntil,
        public readonly DateTimeImmutable  $expiresAt,
        public private(set) bool           $isVerified,
    ) {}

    public static function create(
        string            $phone,
        string            $code,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id:            null,
            phone:         $phone,
            code:          $code,
            attemptsCount: 0,
            blockedUntil:  null,
            expiresAt:     $expiresAt,
            isVerified:    false,
        );
    }

    public function isBlocked(): bool
    {
        return $this->blockedUntil !== null
            && $this->blockedUntil > new DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isValid(string $code): bool
    {
        return ! $this->isExpired()
            && ! $this->isBlocked()
            && ! $this->isVerified
            && hash_equals($this->code, $code);
    }

    public function incrementAttempts(int $maxAttempts = 5, int $blockMinutes = 10): void
    {
        $this->attemptsCount++;

        if ($this->attemptsCount >= $maxAttempts) {
            $this->blockedUntil = new DateTimeImmutable("+{$blockMinutes} minutes");
        }
    }

    public function markAsVerified(): void
    {
        $this->isVerified = true;
    }

    /** Tasdiqlangan OTP muddatini uzaytirish — ro'yxatdan o'tish bosqichlari uchun yetarli vaqt */
    public function extendExpiry(DateTimeImmutable $newExpiry): self
    {
        return new self(
            id:            $this->id,
            phone:         $this->phone,
            code:          $this->code,
            attemptsCount: $this->attemptsCount,
            blockedUntil:  $this->blockedUntil,
            expiresAt:     $newExpiry,
            isVerified:    $this->isVerified,
        );
    }
}
