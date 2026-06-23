<?php

declare(strict_types=1);

namespace Modules\Admin\Application\DTOs;

use Modules\Admin\Presentation\Requests\StaffLoginRequest;

final readonly class StaffLoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(StaffLoginRequest $request): static
    {
        return new static(
            email:    $request->input('email'),
            password: $request->input('password'),
        );
    }
}
