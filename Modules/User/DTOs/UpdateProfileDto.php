<?php

namespace Modules\User\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
        );
    }
}