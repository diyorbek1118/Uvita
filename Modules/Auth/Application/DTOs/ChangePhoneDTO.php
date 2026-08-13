<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ChangePhoneDTO
{
    public function __construct(
        public string  $phone,
        public string  $code,
        public ?string $currentPassword,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            phone:           (string) $request->validated('phone'),
            code:            (string) $request->validated('code'),
            currentPassword: $request->validated('current_password'),
        );
    }
}
