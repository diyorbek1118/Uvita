<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class CreateSupportTicketCommand
{
    public function __construct(
        public int $courierId,
        public ?int $orderId,
        public string $category,
        public string $message,
    ) {}
}
