<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Commands;

use Modules\Listing\Application\DTOs\CreateListingDTO;
use Modules\Listing\Presentation\Requests\CreateListingRequest;

final readonly class CreateListingCommand
{
    public function __construct(public CreateListingDTO $dto) {}

    public static function fromRequest(CreateListingRequest $request, int $sellerId): static
    {
        return new static(dto: CreateListingDTO::fromRequest($request, $sellerId));
    }
}
