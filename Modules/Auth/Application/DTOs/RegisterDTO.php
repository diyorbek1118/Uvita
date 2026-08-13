<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class RegisterDTO
{
    public function __construct(
        public string  $phone,
        public string  $code,
        public string  $name,
        public ?string $surname,
        public ?string $region,
        public ?string $district,
        public ?string $address,
        public ?float  $lat,
        public ?float  $lng,
        public string  $password,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            phone:    (string) $request->validated('phone'),
            code:     (string) $request->validated('code'),
            name:     (string) $request->validated('name'),
            surname:  $request->validated('surname'),
            region:   $request->validated('region'),
            district: $request->validated('district'),
            address:  $request->validated('address'),
            lat:      $request->validated('lat') !== null ? (float) $request->validated('lat') : null,
            lng:      $request->validated('lng') !== null ? (float) $request->validated('lng') : null,
            password: (string) $request->validated('password'),
        );
    }
}
