<?php

declare(strict_types=1);

namespace Modules\Admin\Application\DTOs;

use Illuminate\Http\Request;
use Modules\Admin\Domain\ValueObjects\SettingKey;

final readonly class UpdateSettingDTO
{
    public function __construct(
        public SettingKey $key,
        public string     $value,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            key:   SettingKey::from($request->input('key')),
            value: (string) $request->input('value'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key:   SettingKey::from($data['key']),
            value: (string) $data['value'],
        );
    }
}
