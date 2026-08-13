<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Application\DTOs\ChangePhoneDTO;

final readonly class ChangePhoneCommand
{
    public function __construct(
        public ChangePhoneDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: ChangePhoneDTO::fromRequest($request));
    }
}
