<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use DateTimeImmutable;

final class OtpAttempt
{
    private const MAX_ATTEMPTS  = 5;
    private const BLOCK_MINUTES = 10;

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

    public function incrementAttempts(): void
    {
        $this->attemptsCount++;

        if ($this->attemptsCount >= self::MAX_ATTEMPTS) {
            $this->blockedUntil = new DateTimeImmutable('+' . self::BLOCK_MINUTES . ' minutes');
        }
    }

    public function markAsVerified(): void
    {
        $this->isVerified = true;
    }
}
