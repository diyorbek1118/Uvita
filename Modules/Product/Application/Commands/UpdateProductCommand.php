<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

use Modules\Product\Application\DTOs\UpdateProductDTO;
use Modules\Product\Presentation\Requests\UpdateProductRequest;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int             $id,
        public UpdateProductDTO $dto,
    ) {}

    public static function fromRequest(UpdateProductRequest $request, int $id): self
    {
        return new self(
            id:  $id,
            dto: UpdateProductDTO::fromRequest($request),
        );
    }
}
