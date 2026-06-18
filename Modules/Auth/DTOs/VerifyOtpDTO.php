<?php

namespace Modules\Auth\DTOs;

use Modules\Auth\Enums\OtpTypeEnum;

class VerifyOtpDTO
{
    public function __construct(
        public readonly string      $phone,
        public readonly string      $code,
        public readonly OtpTypeEnum $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            code:  $data['code'],
            type:  OtpTypeEnum::from($data['type']),
        );
    }
}