<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Commands;

use Modules\Deal\Application\DTOs\CreateDealDTO;
use Modules\Deal\Presentation\Requests\CreateDealRequest;

final readonly class CreateDealCommand
{
    public function __construct(public CreateDealDTO $dto) {}

    public static function fromRequest(CreateDealRequest $request, int $buyerId, int $totalPrice): static
    {
        return new static(dto: CreateDealDTO::fromRequest($request, $buyerId, $totalPrice));
    }
}
