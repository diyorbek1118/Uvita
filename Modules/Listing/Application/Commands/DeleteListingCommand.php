<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Commands;

final readonly class DeleteListingCommand
{
    public function __construct(
        public int $listingId,
        public int $sellerId,
    ) {}
}
