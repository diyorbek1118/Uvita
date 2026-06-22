<?php

declare(strict_types=1);

namespace Modules\Category\Application\Commands;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Category\Application\DTOs\CreateCategoryDTO;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public CreateCategoryDTO $dto,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(dto: CreateCategoryDTO::fromRequest($request));
    }
}
