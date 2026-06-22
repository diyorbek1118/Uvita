<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\VerifyOtpDTO;

final readonly class VerifyOtpCommand
{
    public function __construct(
        public VerifyOtpDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: VerifyOtpDTO::fromRequest($request));
    }
}
