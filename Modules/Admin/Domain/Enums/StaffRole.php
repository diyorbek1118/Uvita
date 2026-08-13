<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Enums;

enum StaffRole: string
{
    case SELLER      = 'seller';
    case MANAGER     = 'manager';
    case COURIER     = 'courier';
    case ADMIN       = 'admin';
    case SUPER_ADMIN = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::SELLER      => 'Sotuvchi',
            self::MANAGER     => 'Menejer',
            self::COURIER     => 'Kuryer',
            self::ADMIN       => 'Admin',
            self::SUPER_ADMIN => 'Bosh admin',
        };
    }

    public function canAccessAdminPanel(): bool
    {
        return match ($this) {
            self::ADMIN, self::SUPER_ADMIN => true,
            self::SELLER, self::MANAGER, self::COURIER => false,
        };
    }
}
