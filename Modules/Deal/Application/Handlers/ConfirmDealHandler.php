<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Modules\Deal\Application\Commands\ConfirmDealCommand;
use Modules\Deal\Domain\Exceptions\DealNotFoundException;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;

final class ConfirmDealHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(ConfirmDealCommand $command): DealModel
    {
        $deal = $this->deals->findById($command->dealId)
            ?? throw new DealNotFoundException("Bitim topilmadi.");

        if ($deal->sellerId !== $command->sellerId) {
            abort(403, "Bu bitim sizga tegishli emas.");
        }

        $deal->confirm();
        $this->deals->save($deal);

        return DealModel::with(['listing.seller', 'listing.category', 'buyer'])->findOrFail($command->dealId);
    }
}
