<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ChangePasswordDTO
{
    public function __construct(
        public ?string $currentPassword,
        public string  $newPassword,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            currentPassword: $request->validated('current_password'),
            newPassword:     (string) $request->validated('new_password'),
        );
    }
}
