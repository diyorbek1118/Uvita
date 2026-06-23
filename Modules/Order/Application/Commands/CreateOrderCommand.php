<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

use Modules\Order\Application\DTOs\CreateOrderDTO;
use Modules\Order\Presentation\Requests\CreateOrderRequest;

final readonly class CreateOrderCommand
{
    public function __construct(public CreateOrderDTO $dto) {}

    public static function fromRequest(CreateOrderRequest $request, int $userId): static
    {
        return new static(dto: CreateOrderDTO::fromRequest($request, $userId));
    }
}
