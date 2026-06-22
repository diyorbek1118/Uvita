<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Entities;

use DateTimeImmutable;

final class Category
{
    public function __construct(
        public readonly ?int              $id,
        public readonly string            $name,
        public readonly string            $slug,
        public readonly ?string           $image,
        public readonly ?int              $parentId,
        public readonly bool              $isActive,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string  $name,
        string  $slug,
        ?string $image    = null,
        ?int    $parentId = null,
    ): self {
        return new self(
            id:        null,
            name:      $name,
            slug:      $slug,
            image:     $image,
            parentId:  $parentId,
            isActive:  true,
            createdAt: null,
            updatedAt: null,
        );
    }

    public function modify(
        string  $name,
        string  $slug,
        ?string $image,
        ?int    $parentId,
        bool    $isActive,
    ): self {
        return new self(
            id:        $this->id,
            name:      $name,
            slug:      $slug,
            image:     $image,
            parentId:  $parentId,
            isActive:  $isActive,
            createdAt: $this->createdAt,
            updatedAt: null,
        );
    }
}
