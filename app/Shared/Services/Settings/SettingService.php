<?php

declare(strict_types=1);

namespace App\Shared\Services\Settings;

use Illuminate\Support\Facades\Cache;
use Modules\Admin\Domain\ValueObjects\SettingKey;
use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

final class SettingService
{
    public function get(SettingKey $key): string
    {
        return Cache::remember(
            "setting_{$key->value}",
            now()->addHours(24),
            fn () => SettingModel::where('key', $key->value)->value('value') ?? '',
        );
    }

    public function deliveryPrice(): int
    {
        return (int) $this->get(SettingKey::DELIVERY_PRICE);
    }

    public function deliveryCity(): string
    {
        return $this->get(SettingKey::DELIVERY_CITY);
    }

    public function minOrderAmount(): int
    {
        return (int) $this->get(SettingKey::MIN_ORDER_AMOUNT);
    }

    public function otpExpirySeconds(): int
    {
        return (int) $this->get(SettingKey::OTP_EXPIRY_SECONDS);
    }

    public function otpMaxAttempts(): int
    {
        return (int) $this->get(SettingKey::OTP_MAX_ATTEMPTS);
    }

    public function otpBlockMinutes(): int
    {
        return (int) $this->get(SettingKey::OTP_BLOCK_MINUTES);
    }

    public function maxNotFoundAttempts(): int
    {
        return (int) $this->get(SettingKey::MAX_NOT_FOUND_ATTEMPTS);
    }

    public function reviewRequestDelayHours(): int
    {
        return (int) $this->get(SettingKey::REVIEW_REQUEST_DELAY);
    }
}
