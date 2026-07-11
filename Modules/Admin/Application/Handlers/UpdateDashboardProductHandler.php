<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

/**
 * Dashboard mahsulot tahriri — ownership guard'i bilan.
 * Manager faqat o'z mahsulotini tahrirlaydi; admin/super har qanday.
 */
final class UpdateDashboardProductHandler
{
    public function __construct(
        private readonly UpdateProductHandler $updateHandler,
    ) {}

    public function handle(UpdateProductCommand $command, Staff $actor): ProductModel
    {
        $product = ProductModel::findOrFail($command->id);

        if ($actor->role === StaffRole::MANAGER && $product->manager_id !== $actor->id) {
            abort(403, "Bu mahsulotni tahrirlash huquqingiz yo'q");
        }

        return $this->updateHandler->handle($command);
    }
}
