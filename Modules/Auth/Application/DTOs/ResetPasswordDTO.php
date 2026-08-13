<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $phone,
        public string $code,
        public string $password,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            phone:    (string) $request->validated('phone'),
            code:     (string) $request->validated('code'),
            password: (string) $request->validated('password'),
        );
    }
}
