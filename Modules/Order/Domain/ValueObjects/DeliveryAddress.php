<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObjects;

final readonly class DeliveryAddress
{
    public function __construct(
        public string  $region,
        public string  $district,
        public string  $street,
        public string  $house,
        public ?string $landmark = null,
    ) {}

    public function toArray(): array
    {
        return [
            'region'   => $this->region,
            'district' => $this->district,
            'street'   => $this->street,
            'house'    => $this->house,
            'landmark' => $this->landmark,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            region:   $data['region'],
            district: $data['district'],
            street:   $data['street'],
            house:    $data['house'],
            landmark: $data['landmark'] ?? null,
        );
    }
}
