<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\ConfirmOtpDTO;

final readonly class ConfirmOtpCommand
{
    public function __construct(
        public ConfirmOtpDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: ConfirmOtpDTO::fromRequest($request));
    }
}
