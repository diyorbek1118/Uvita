<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class ResolveSupportTicketCommand
{
    public function __construct(
        public int $ticketId,
        public int $adminId,
        public string $status,
        public ?string $reply,
    ) {}
}
