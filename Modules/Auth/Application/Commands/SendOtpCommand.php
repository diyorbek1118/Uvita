<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\SendOtpDTO;

final readonly class SendOtpCommand
{
    public function __construct(
        public SendOtpDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: SendOtpDTO::fromRequest($request));
    }
}
