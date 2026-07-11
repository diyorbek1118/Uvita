<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Queries\GetAllSettingsQuery;
use Modules\Admin\Domain\Repositories\SettingRepositoryInterface;
use Modules\Admin\Domain\ValueObjects\SettingKey;

final class GetAllSettingsHandler
{
    public function __construct(
        private readonly SettingRepositoryInterface $settings,
    ) {}

    public function handle(GetAllSettingsQuery $query): array
    {
        $all = [];
        foreach ($this->settings->findAll() as $setting) {
            $all[$setting->key->value] = $setting->value;
        }

        return [
            'delivery' => [
                'delivery_city'   => $all[SettingKey::DELIVERY_CITY->value]    ?? null,
                'min_order_amount'=> $all[SettingKey::MIN_ORDER_AMOUNT->value] ?? null,
            ],
            'otp' => [
                'otp_expiry_seconds' => $all[SettingKey::OTP_EXPIRY_SECONDS->value] ?? null,
                'otp_max_attempts'   => $all[SettingKey::OTP_MAX_ATTEMPTS->value]   ?? null,
                'otp_block_minutes'  => $all[SettingKey::OTP_BLOCK_MINUTES->value]  ?? null,
            ],
            'order' => [
                'max_not_found_attempts'   => $all[SettingKey::MAX_NOT_FOUND_ATTEMPTS->value] ?? null,
                'review_request_delay_hours' => $all[SettingKey::REVIEW_REQUEST_DELAY->value] ?? null,
            ],
        ];
    }
}
