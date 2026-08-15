<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Domain\Enums\ProductStatusEnum;
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
        return DB::transaction(function () use ($command, $actor): ProductModel {
            $product = ProductModel::query()->lockForUpdate()->findOrFail($command->id);

            if ($actor->role === StaffRole::MANAGER && $product->manager_id !== $actor->id) {
                abort(403, "Bu mahsulotni tahrirlash huquqingiz yo'q");
            }
            if ($command->dto->stock < $product->reserved_stock) {
                throw new DomainException(
                    "Stokni {$product->reserved_stock} dan kamaytirib bo‘lmaydi: bu miqdor faol buyurtmalar uchun rezerv qilingan."
                );
            }

            $updated = $this->updateHandler->handle($command);

            // Manager tahrirlagan har qanday mahsulot qayta moderatsiyadan o'tadi.
            if ($actor->role === StaffRole::MANAGER) {
                $updated->forceFill([
                    'status' => ProductStatusEnum::Inactive->value,
                    'rejection_reason' => null,
                ])->save();
            }

            return $updated->fresh();
        });
    }
}
