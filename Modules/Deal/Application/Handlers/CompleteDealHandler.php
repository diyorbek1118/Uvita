<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Modules\Deal\Application\Commands\CompleteDealCommand;
use Modules\Deal\Domain\Exceptions\DealNotFoundException;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class CompleteDealHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(CompleteDealCommand $command): DealModel
    {
        $deal = $this->deals->findById($command->dealId)
            ?? throw new DealNotFoundException('Bitim topilmadi.');

        if (! in_array($command->userId, [$deal->sellerId, $deal->buyerId], true)) {
            abort(403, 'Bu bitim sizga tegishli emas.');
        }

        $deal->complete();
        $this->deals->save($deal);

        // E'lon sotilgan deb belgilanadi
        ListingModel::where('id', $deal->listingId)->update(['status' => 'sold']);

        return DealModel::with(['listing.seller', 'listing.category', 'buyer'])->findOrFail($command->dealId);
    }
}
