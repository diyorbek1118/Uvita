<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Modules\Category\Application\Commands\CreateCategoryCommand;
use Modules\Category\Domain\Entities\Category as CategoryEntity;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;

final class CreateCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(CreateCategoryCommand $command): CategoryModel
    {
        $dto = $command->dto;

        $entity = CategoryEntity::create(
            name:     $dto->name,
            slug:     $dto->slug,
            image:    $dto->image,
            parentId: $dto->parentId,
        );

        $saved = $this->categories->save($entity);

        return CategoryModel::findOrFail($saved->id);
    }
}
