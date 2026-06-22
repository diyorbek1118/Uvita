<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeImmutable;
use Modules\Product\Domain\Enums\ProductStatusEnum;

final readonly class Product
{
    public function __construct(
        public ?int               $id,
        public string             $name,
        public string             $slug,
        public string             $description,
        public int                $price,
        public int                $stock,
        public ProductStatusEnum  $status,
        public array              $images,
        public int                $categoryId,
        public ?int               $managerId,
        public ?string            $rejectionReason,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $name,
        string $slug,
        string $description,
        int $price,
        int $stock,
        array $images,
        int $categoryId,
        ?int $managerId = null,
    ): self {
        // Manager yaratsa → inactive (approval kerak), Admin/Super Admin yaratsa → active
        $status = $managerId !== null
            ? ProductStatusEnum::Inactive
            : ProductStatusEnum::Active;

        return new self(
            id:              null,
            name:            $name,
            slug:            $slug,
            description:     $description,
            price:           $price,
            stock:           $stock,
            status:          $status,
            images:          $images,
            categoryId:      $categoryId,
            managerId:       $managerId,
            rejectionReason: null,
            createdAt:       null,
            updatedAt:       null,
        );
    }

    public function approve(): self
    {
        return new self(
            id:              $this->id,
            name:            $this->name,
            slug:            $this->slug,
            description:     $this->description,
            price:           $this->price,
            stock:           $this->stock,
            status:          ProductStatusEnum::Active,
            images:          $this->images,
            categoryId:      $this->categoryId,
            managerId:       $this->managerId,
            rejectionReason: null,
            createdAt:       $this->createdAt,
            updatedAt:       $this->updatedAt,
        );
    }

    public function reject(string $reason): self
    {
        return new self(
            id:              $this->id,
            name:            $this->name,
            slug:            $this->slug,
            description:     $this->description,
            price:           $this->price,
            stock:           $this->stock,
            status:          ProductStatusEnum::Rejected,
            images:          $this->images,
            categoryId:      $this->categoryId,
            managerId:       $this->managerId,
            rejectionReason: $reason,
            createdAt:       $this->createdAt,
            updatedAt:       $this->updatedAt,
        );
    }

    public function modify(
        string $name,
        string $slug,
        string $description,
        int $price,
        int $stock,
        array $images,
        int $categoryId,
    ): self {
        return new self(
            id:              $this->id,
            name:            $name,
            slug:            $slug,
            description:     $description,
            price:           $price,
            stock:           $stock,
            status:          $this->status,
            images:          $images,
            categoryId:      $categoryId,
            managerId:       $this->managerId,
            rejectionReason: $this->rejectionReason,
            createdAt:       $this->createdAt,
            updatedAt:       $this->updatedAt,
        );
    }
}
