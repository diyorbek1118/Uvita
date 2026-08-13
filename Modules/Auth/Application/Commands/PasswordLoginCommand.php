<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\PasswordLoginDTO;

final readonly class PasswordLoginCommand
{
    public function __construct(
        public PasswordLoginDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: PasswordLoginDTO::fromRequest($request));
    }
}
