<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Application\Commands\ResolveSupportTicketCommand;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Domain\Enums\SupportTicketStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierSupportTicket;

final class ResolveCourierSupportTicketHandler
{
    public function __construct(private readonly CourierNotifierInterface $notifier) {}

    public function handle(ResolveSupportTicketCommand $command): CourierSupportTicket
    {
        $ticket = CourierSupportTicket::findOrFail($command->ticketId);
        $status = SupportTicketStatus::from($command->status);
        $ticket->update([
            'status' => $status,
            'admin_reply' => $command->reply,
            'resolved_by' => $status === SupportTicketStatus::RESOLVED ? $command->adminId : null,
            'resolved_at' => $status === SupportTicketStatus::RESOLVED ? now() : null,
        ]);

        $this->notifier->notify(
            $ticket->courier_id,
            'support_updated',
            'Yordam so‘rovi yangilandi',
            "Yordam so‘rovi #{$ticket->id}: {$status->value}",
            ['ticket_id' => $ticket->id]
        );

        return $ticket->fresh();
    }
}
