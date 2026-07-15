<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

final readonly class PhoneNumber
{
    public const PATTERN = '/^\+998(33|50|77|88|90|91|93|94|95|97|98|99)[0-9]{7}$/';

    public string $value;

    public function __construct(string $phone)
    {
        if (! preg_match(self::PATTERN, $phone)) {
            throw new \InvalidArgumentException(
                "Telefon raqami +998 va to'g'ri mobil operator kodi bilan bo'lishi kerak "
                . "(masalan +998901234567). Berilgan: {$phone}"
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
