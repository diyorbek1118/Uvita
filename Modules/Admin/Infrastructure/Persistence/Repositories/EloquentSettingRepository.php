<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Repositories;

use Modules\Admin\Domain\Entities\Setting;
use Modules\Admin\Domain\Repositories\SettingRepositoryInterface;
use Modules\Admin\Domain\ValueObjects\SettingKey;
use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

final class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function findByKey(SettingKey $key): ?Setting
    {
        $model = SettingModel::where('key', $key->value)->first();

        return $model?->toDomainEntity();
    }

    public function findAll(): array
    {
        return SettingModel::all()
            ->map(fn (SettingModel $m) => $m->toDomainEntity())
            ->values()
            ->all();
    }

    public function save(Setting $setting): void
    {
        SettingModel::where('key', $setting->key->value)
            ->update(['value' => $setting->value]);
    }
}
