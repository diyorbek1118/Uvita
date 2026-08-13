<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class CreateCourierPayoutCommand
{
    public function __construct(
        public int $courierId,
        public string $periodStart,
        public string $periodEnd,
        public ?string $note,
        public int $createdBy,
    ) {}
}
