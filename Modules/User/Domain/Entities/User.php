<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly ?int               $id,
        public readonly ?string            $name,
        public readonly string             $phone,
        public readonly ?DateTimeImmutable $createdAt,
    ) {}

    public static function create(string $phone, ?string $name = null): self
    {
        return new self(
            id:        null,
            name:      $name,
            phone:     $phone,
            createdAt: null,
        );
    }

    public function withName(string $name): self
    {
        return new self(
            id:        $this->id,
            name:      $name,
            phone:     $this->phone,
            createdAt: $this->createdAt,
        );
    }
}
