<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Contracts;

interface CourierNotifierInterface
{
    /** @param array<string, mixed> $data */
    public function notify(int $courierId, string $type, string $title, string $body, array $data = []): void;
}
