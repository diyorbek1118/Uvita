<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Handlers;

use Modules\Listing\Application\Commands\UpdateListingCommand;
use Modules\Listing\Domain\Exceptions\ListingNotFoundException;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class UpdateListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
    ) {}

    public function handle(UpdateListingCommand $command): ListingModel
    {
        $listing = $this->listings->findById($command->listingId)
            ?? throw new ListingNotFoundException("E'lon topilmadi.");

        if ($listing->sellerId !== $command->sellerId) {
            abort(403, "Bu e'lon sizga tegishli emas.");
        }

        $dto = $command->dto;
        $listing->update(
            categoryId: $dto->categoryId,
            title: $dto->title,
            description: $dto->description,
            price: $dto->price,
            quantity: $dto->quantity,
            unit: $dto->unit,
            images: $dto->images,
            video: $dto->video,
            region: $dto->region,
            district: $dto->district,
            address: $dto->address,
            lat: $dto->lat,
            lng: $dto->lng,
            details: $dto->details,
            contacts: $dto->contacts,
        );

        $this->listings->save($listing);

        return ListingModel::findOrFail($command->listingId);
    }
}
