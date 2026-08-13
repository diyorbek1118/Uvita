<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Modules\Deal\Application\Commands\CreateDealCommand;
use Modules\Deal\Domain\Entities\Deal;
use Modules\Deal\Domain\Exceptions\DealNotFoundException;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class CreateDealHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(CreateDealCommand $command): DealModel
    {
        $dto = $command->dto;
        $listing = ListingModel::with('seller')->find($dto->listingId)
            ?? throw new DealNotFoundException("E'lon topilmadi.");

        // Faqat faol e'lonlarga buyurtma berish mumkin
        if ($listing->status->value !== 'active') {
            abort(422, "Bu e'lon hozircha sotuvda emas.");
        }

        // O'z e'loniga buyurtma bermaslik
        if ($listing->seller_id === $dto->buyerId) {
            abort(422, "O'z e'loningizga buyurtma bera olmaysiz.");
        }

        // Mavjud miqdordan oshmasligi
        if ((float) $listing->quantity < $dto->quantity) {
            abort(422, "So'ralgan miqdor mavjud e'lon miqdoridan oshib ketdi.");
        }

        $deal = new Deal(
            id:         null,
            listingId:  $dto->listingId,
            sellerId:   $listing->seller_id,
            buyerId:    $dto->buyerId,
            quantity:   $dto->quantity,
            unit:       $dto->unit,
            totalPrice: $dto->totalPrice,
        );

        $id = $this->deals->save($deal);

        return DealModel::with(['listing.seller', 'listing.category', 'buyer'])->findOrFail($id);
    }
}
