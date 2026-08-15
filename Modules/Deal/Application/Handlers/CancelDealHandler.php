<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Modules\Deal\Application\Commands\CancelDealCommand;
use Modules\Deal\Domain\Exceptions\DealNotFoundException;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;

final class CancelDealHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(CancelDealCommand $command): DealModel
    {
        $deal = $this->deals->findById($command->dealId)
            ?? throw new DealNotFoundException('Bitim topilmadi.');

        if (! in_array($command->userId, [$deal->sellerId, $deal->buyerId], true)) {
            abort(403, 'Bu bitim sizga tegishli emas.');
        }

        $deal->cancel();
        $this->deals->save($deal);

        return DealModel::with(['listing.seller', 'listing.category', 'buyer'])->findOrFail($command->dealId);
    }
}
