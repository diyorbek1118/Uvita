<?php

declare(strict_types=1);

namespace Modules\Listing\Domain\Enums;

enum ListingStatus: string
{
    case PENDING  = 'pending';   // Kutilmoqda (moderatsiya)
    case ACTIVE   = 'active';    // Faol
    case REJECTED = 'rejected';  // Rad etilgan
    case SOLD     = 'sold';      // Sotilgan

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Kutilmoqda',
            self::ACTIVE   => 'Faol',
            self::REJECTED => 'Rad etilgan',
            self::SOLD     => 'Sotilgan',
        };
    }
}
