<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Presentation\Requests\CreateProductRequest;

final readonly class CreateProductCommand
{
    public function __construct(
        public CreateProductDTO $dto,
    ) {}

    public static function fromRequest(CreateProductRequest $request, ?int $managerId = null): self
    {
        return new self(
            dto: CreateProductDTO::fromRequest($request, $managerId),
        );
    }
}
