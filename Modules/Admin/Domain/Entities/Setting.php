<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Entities;

use Modules\Admin\Domain\ValueObjects\SettingKey;

final readonly class Setting
{
    public function __construct(
        public ?int       $id,
        public SettingKey $key,
        public string     $value,
        public ?string    $description,
    ) {}

    public function withValue(string $value): self
    {
        return new self($this->id, $this->key, $value, $this->description);
    }
}
