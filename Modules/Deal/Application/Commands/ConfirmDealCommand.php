<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Commands;

final readonly class ConfirmDealCommand
{
    public function __construct(
        public int $dealId,
        public int $sellerId,
    ) {}
}
