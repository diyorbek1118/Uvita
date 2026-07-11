<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\Cache;
use Modules\Admin\Application\Commands\UpdateSettingCommand;
use Modules\Admin\Domain\Exceptions\SettingNotFoundException;
use Modules\Admin\Domain\Repositories\SettingRepositoryInterface;
use Modules\Admin\Domain\ValueObjects\SettingKey;

final class UpdateSettingHandler
{
    public function __construct(
        private readonly SettingRepositoryInterface $settings,
    ) {}

    public function handle(UpdateSettingCommand $command): void
    {
        $dto     = $command->dto;
        $setting = $this->settings->findByKey($dto->key);

        if ($setting === null) {
            throw new SettingNotFoundException($dto->key->value);
        }

        $this->validateValue($dto->key, $dto->value);

        $this->settings->save($setting->withValue($dto->value));
        Cache::forget("setting_{$dto->key->value}");
    }

    private function validateValue(SettingKey $key, string $value): void
    {
        match ($key) {
            SettingKey::MIN_ORDER_AMOUNT       => $this->assertPositiveInt($value, 'Minimal buyurtma summasi'),
            SettingKey::OTP_EXPIRY_SECONDS     => $this->assertIntInRange($value, 60, 600, 'OTP muddati'),
            SettingKey::OTP_MAX_ATTEMPTS       => $this->assertIntInRange($value, 1, 10, 'OTP urinishlar soni'),
            SettingKey::OTP_BLOCK_MINUTES      => $this->assertIntInRange($value, 1, 60, 'OTP blok vaqti'),
            SettingKey::MAX_NOT_FOUND_ATTEMPTS => $this->assertIntInRange($value, 1, 10, 'Topilmadi urinishlari'),
            SettingKey::REVIEW_REQUEST_DELAY   => $this->assertIntInRange($value, 1, 168, 'Review kechikish vaqti'),
            SettingKey::DELIVERY_CITY          => $this->assertNonEmpty($value, 'Shahar nomi'),
        };
    }

    private function assertPositiveInt(string $value, string $label): void
    {
        if (!ctype_digit($value) || (int) $value <= 0) {
            abort(422, "{$label} musbat son bo'lishi kerak");
        }
    }

    private function assertIntInRange(string $value, int $min, int $max, string $label): void
    {
        if (!ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
            abort(422, "{$label} {$min} va {$max} orasida bo'lishi kerak");
        }
    }

    private function assertNonEmpty(string $value, string $label): void
    {
        if (trim($value) === '') {
            abort(422, "{$label} bo'sh bo'lishi mumkin emas");
        }
    }
}
