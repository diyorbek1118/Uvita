<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;

final class DeleteProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(DeleteProductCommand $command): void
    {
        $this->products->delete($command->id);
    }
}
