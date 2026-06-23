<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Repositories;

use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Enums\OrderStatus;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    public function findByUserId(int $userId): array;
    public function findByStatus(OrderStatus $status): array;
    public function save(Order $order): Order;
    public function findPaidOrders(): array;
    public function findReadyToDeliverOrders(): array;
}
