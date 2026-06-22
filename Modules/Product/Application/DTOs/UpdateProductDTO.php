<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Product\Presentation\Requests\UpdateProductRequest;

final readonly class UpdateProductDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public int    $price,
        public int    $stock,
        public array  $images,
        public int    $categoryId,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        $name = $request->validated('name');

        return new self(
            name:        $name,
            slug:        $request->validated('slug'),
            description: $request->validated('description'),
            price:       $request->validated('price'),
            stock:       $request->validated('stock'),
            images:      $request->validated('images', []),
            categoryId:  $request->validated('category_id'),
        );
    }
}
