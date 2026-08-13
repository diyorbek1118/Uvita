<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Courier\Application\Commands\CreateSupportTicketCommand;
use Modules\Courier\Domain\Enums\SupportTicketStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierSupportTicket;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CreateCourierSupportTicketHandler
{
    public function handle(CreateSupportTicketCommand $command): CourierSupportTicket
    {
        if ($command->orderId !== null && ! OrderModel::query()
            ->where('id', $command->orderId)
            ->where('courier_id', $command->courierId)
            ->exists()) {
            throw new ModelNotFoundException('Buyurtma topilmadi.');
        }

        return CourierSupportTicket::create([
            'courier_id' => $command->courierId,
            'order_id' => $command->orderId,
            'category' => $command->category,
            'message' => $command->message,
            'status' => SupportTicketStatus::OPEN,
        ]);
    }
}
