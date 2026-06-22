<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Category\Application\Commands\UpdateCategoryCommand;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;

final class UpdateCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(UpdateCategoryCommand $command): CategoryModel
    {
        $existing = $this->categories->findById($command->id);

        if ($existing === null) {
            throw new ModelNotFoundException();
        }

        $dto     = $command->dto;
        $updated = $existing->modify(
            name:     $dto->name,
            slug:     $dto->slug,
            image:    $dto->image,
            parentId: $dto->parentId,
            isActive: $dto->isActive,
        );

        $saved = $this->categories->save($updated);

        return CategoryModel::findOrFail($saved->id);
    }
}
