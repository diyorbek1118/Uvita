<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Handlers;

use Modules\Listing\Application\Commands\CreateListingCommand;
use Modules\Listing\Domain\Entities\Listing;
use Modules\Listing\Domain\Enums\ListingStatus;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class CreateListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
    ) {}

    public function handle(CreateListingCommand $command): ListingModel
    {
        $dto = $command->dto;

        $listing = new Listing(
            id: null,
            sellerId: $dto->sellerId,
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
            expiresAt: $dto->expiresAt !== null ? new \DateTimeImmutable($dto->expiresAt) : null,
            // Hozircha moderatsiyasiz — e'lon darhol faol bo'lib bozorda ko'rinadi (keyinroq admin tasdiqlash qo'shiladi)
            status: ListingStatus::ACTIVE,
        );

        $id = $this->listings->save($listing);

        return ListingModel::findOrFail($id);
    }
}
