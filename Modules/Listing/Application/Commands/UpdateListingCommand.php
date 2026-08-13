<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Commands;

use Modules\Listing\Application\DTOs\UpdateListingDTO;
use Modules\Listing\Presentation\Requests\UpdateListingRequest;

final readonly class UpdateListingCommand
{
    public function __construct(
        public int             $listingId,
        public int             $sellerId,
        public UpdateListingDTO $dto,
    ) {}

    public static function fromRequest(int $listingId, int $sellerId, UpdateListingRequest $request): static
    {
        return new static(
            listingId: $listingId,
            sellerId:  $sellerId,
            dto:       UpdateListingDTO::fromRequest($request),
        );
    }
}
