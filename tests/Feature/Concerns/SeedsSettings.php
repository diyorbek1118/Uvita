<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

trait SeedsSettings
{
    protected function seedSettings(array $overrides = []): void
    {
        $defaults = [
            'delivery_city'            => 'Toshkent',
            'min_order_amount'         => '50000',
            'otp_expiry_seconds'       => '120',
            'otp_max_attempts'         => '5',
            'otp_block_minutes'        => '10',
            'max_not_found_attempts'   => '3',
            'review_request_delay_hours' => '24',
        ];

        foreach (array_merge($defaults, $overrides) as $key => $value) {
            SettingModel::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
