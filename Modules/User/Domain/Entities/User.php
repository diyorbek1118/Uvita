<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $surname,
        public readonly string $phone,
        public readonly ?string $region,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly ?string $password,
        public readonly ?DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string $phone,
        ?string $name = null,
        ?string $surname = null,
        ?string $region = null,
        ?string $district = null,
        ?string $address = null,
        ?float $lat = null,
        ?float $lng = null,
        ?string $password = null,
    ): self {
        return new self(
            id: null,
            name: $name,
            surname: $surname,
            phone: $phone,
            region: $region,
            district: $district,
            address: $address,
            lat: $lat,
            lng: $lng,
            password: $password,
            createdAt: null,
        );
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            name: $name,
            surname: $this->surname,
            phone: $this->phone,
            region: $this->region,
            district: $this->district,
            address: $this->address,
            lat: $this->lat,
            lng: $this->lng,
            password: $this->password,
            createdAt: $this->createdAt,
        );
    }

    public function withPassword(string $password): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            surname: $this->surname,
            phone: $this->phone,
            region: $this->region,
            district: $this->district,
            address: $this->address,
            lat: $this->lat,
            lng: $this->lng,
            password: $password,
            createdAt: $this->createdAt,
        );
    }

    public function withPhone(string $phone): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            surname: $this->surname,
            phone: $phone,
            region: $this->region,
            district: $this->district,
            address: $this->address,
            lat: $this->lat,
            lng: $this->lng,
            password: $this->password,
            createdAt: $this->createdAt,
        );
    }
}
