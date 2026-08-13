<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

final readonly class SellerProductDraftDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public int $price,
        public int $stock,
        public int $categoryId,
        public array $images,
        public int $primaryImageIndex,
        public string $videoUrl,
        public string $originRegion,
        public string $farmerName,
        public string $unit,
        public int $minimumOrderQuantity,
        public bool $termsAccepted,
    ) {}

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'category_id' => $this->categoryId,
            'images' => $this->images,
            'primary_image_index' => $this->primaryImageIndex,
            'video_url' => $this->videoUrl,
            'origin_region' => $this->originRegion,
            'farmer_name' => $this->farmerName,
            'unit' => $this->unit,
            'minimum_order_quantity' => $this->minimumOrderQuantity,
        ];
    }
}
