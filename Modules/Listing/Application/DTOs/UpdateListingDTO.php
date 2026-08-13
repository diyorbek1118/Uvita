<?php

declare(strict_types=1);

namespace Modules\Listing\Application\DTOs;

use Modules\Listing\Presentation\Requests\UpdateListingRequest;

final readonly class UpdateListingDTO
{
    public function __construct(
        public ?int     $categoryId,
        public string   $title,
        public ?string  $description,
        public int      $price,
        public float    $quantity,
        public string   $unit,
        public ?array   $images,
        public ?string  $video,
        public ?string  $region,
        public ?string  $district,
        public ?string  $address,
        public ?float   $lat,
        public ?float   $lng,
        public ?array   $details,
        public ?array   $contacts,
    ) {}

    public static function fromRequest(UpdateListingRequest $request): static
    {
        return new static(
            categoryId:  $request->input('category_id') !== null ? (int) $request->input('category_id') : null,
            title:       $request->input('title'),
            description: $request->input('description'),
            price:       (int) $request->input('price'),
            quantity:    (float) $request->input('quantity', 0),
            unit:        $request->input('unit', 'kg'),
            images:      $request->input('images'),
            video:       $request->input('video'),
            region:      $request->input('region'),
            district:    $request->input('district'),
            address:     $request->input('address'),
            lat:         $request->input('lat') !== null ? (float) $request->input('lat') : null,
            lng:         $request->input('lng') !== null ? (float) $request->input('lng') : null,
            details:     $request->input('details'),
            contacts:    $request->input('contacts'),
        );
    }
}
