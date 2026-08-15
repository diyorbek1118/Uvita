<?php

declare(strict_types=1);

namespace Modules\Deal\Domain\Enums;

enum DealStatus: string
{
    case PENDING = 'pending';    // Kutilmoqda
    case CONFIRMED = 'confirmed';  // Tasdiqlangan
    case COMPLETED = 'completed';  // Yakunlangan
    case CANCELLED = 'cancelled';  // Bekor qilingan

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Kutilmoqda',
            self::CONFIRMED => 'Tasdiqlangan',
            self::COMPLETED => 'Yakunlangan',
            self::CANCELLED => 'Bekor qilingan',
        };
    }
}
