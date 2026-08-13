<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Handlers;

use Modules\Listing\Application\Commands\DeleteListingCommand;
use Modules\Listing\Domain\Exceptions\ListingNotFoundException;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;

final class DeleteListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
    ) {}

    public function handle(DeleteListingCommand $command): void
    {
        $listing = $this->listings->findById($command->listingId)
            ?? throw new ListingNotFoundException("E'lon topilmadi.");

        if ($listing->sellerId !== $command->sellerId) {
            abort(403, "Bu e'lon sizga tegishli emas.");
        }

        $this->listings->delete($command->listingId);
    }
}
