<?php

declare(strict_types=1);

namespace Modules\Payment\Application\DTOs;

use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final readonly class CreatePaymentDTO
{
    public function __construct(
        public int    $orderId,
        public int    $amount,
        public string $provider,
    ) {}

    public static function fromOrder(OrderModel $order, string $provider): static
    {
        return new static(
            orderId:  $order->id,
            amount:   $order->grand_total * 100, // so'm → tiyins
            provider: $provider,
        );
    }
}
