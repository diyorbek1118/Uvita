<?php

declare(strict_types=1);

namespace Modules\Deal\Application\DTOs;

use Modules\Deal\Presentation\Requests\CreateDealRequest;

final readonly class CreateDealDTO
{
    public function __construct(
        public int $listingId,
        public int $buyerId,
        public float $quantity,
        public string $unit,
        public int $totalPrice,
    ) {}

    public static function fromRequest(CreateDealRequest $request, int $buyerId, int $totalPrice): static
    {
        return new self(
            listingId: (int) $request->input('listing_id'),
            buyerId: $buyerId,
            quantity: (float) $request->input('quantity'),
            unit: $request->input('unit', 'kg'),
            totalPrice: $totalPrice,
        );
    }
}
