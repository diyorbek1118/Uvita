<?php

declare(strict_types=1);

namespace Modules\Deal\Domain\Entities;

use Modules\Deal\Domain\Enums\DealStatus;

final class Deal
{
    public private(set) float $quantity;

    public private(set) string $unit;

    public private(set) int $totalPrice;

    public private(set) DealStatus $status;

    public function __construct(
        public readonly ?int $id,
        public readonly int $listingId,
        public readonly int $sellerId,
        public readonly int $buyerId,
        float $quantity,
        string $unit,
        int $totalPrice,
        DealStatus $status = DealStatus::PENDING,
    ) {
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->totalPrice = $totalPrice;
        $this->status = $status;
    }

    public function confirm(): void
    {
        $this->status = DealStatus::CONFIRMED;
    }

    public function complete(): void
    {
        $this->status = DealStatus::COMPLETED;
    }

    public function cancel(): void
    {
        $this->status = DealStatus::CANCELLED;
    }
}
