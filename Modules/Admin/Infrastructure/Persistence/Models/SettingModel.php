<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Domain\Entities\Setting;
use Modules\Admin\Domain\ValueObjects\SettingKey;

class SettingModel extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'description'];

    public function toDomainEntity(): Setting
    {
        return new Setting(
            id:          $this->id,
            key:         SettingKey::from($this->key),
            value:       $this->value,
            description: $this->description,
        );
    }
}
