<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\ChangePasswordDTO;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public ChangePasswordDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: ChangePasswordDTO::fromRequest($request));
    }
}
