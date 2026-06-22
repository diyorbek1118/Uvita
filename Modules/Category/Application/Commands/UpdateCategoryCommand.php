<?php

declare(strict_types=1);

namespace Modules\Category\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Category\Application\DTOs\UpdateCategoryDTO;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public int               $id,
        public UpdateCategoryDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request, int $id): self
    {
        return new self(id: $id, dto: UpdateCategoryDTO::fromRequest($request));
    }
}
