<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

final readonly class PhoneNumber
{
    public string $value;

    public function __construct(string $phone)
    {
        if (! preg_match('/^\+998[0-9]{9}$/', $phone)) {
            throw new \InvalidArgumentException(
                "Telefon raqami +998XXXXXXXXX formatida bo'lishi kerak. Berilgan: {$phone}"
            );
        }

        $this->value = $phone;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
