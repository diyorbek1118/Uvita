<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Repositories;

use Modules\Admin\Domain\Entities\Setting;
use Modules\Admin\Domain\ValueObjects\SettingKey;

interface SettingRepositoryInterface
{
    public function findByKey(SettingKey $key): ?Setting;

    /** @return Setting[] */
    public function findAll(): array;

    public function save(Setting $setting): void;
}
