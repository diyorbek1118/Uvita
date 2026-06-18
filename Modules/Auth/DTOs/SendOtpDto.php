<?php

namespace Modules\Auth\DTOs;

use Modules\Auth\Enums\OtpTypeEnum;

class SendOtpDTO
{
    public function __construct(
        public readonly string      $phone,
        public readonly OtpTypeEnum $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            type:  OtpTypeEnum::from($data['type']),
        );
    }
}