<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Category\Application\Commands\DeleteCategoryCommand;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class DeleteCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(DeleteCategoryCommand $command): void
    {
        if ($this->categories->findById($command->id) === null) {
            throw new ModelNotFoundException;
        }

        if (Product::withTrashed()->where('category_id', $command->id)->exists()) {
            abort(422, "Mahsulot bog'langan kategoriyani o'chirib bo'lmaydi");
        }

        $this->categories->delete($command->id);
    }
}
