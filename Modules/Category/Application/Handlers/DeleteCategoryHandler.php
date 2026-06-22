<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Category\Application\Commands\DeleteCategoryCommand;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;

final class DeleteCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(DeleteCategoryCommand $command): void
    {
        if ($this->categories->findById($command->id) === null) {
            throw new ModelNotFoundException();
        }

        $this->categories->delete($command->id);
    }
}
