<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Modules\Admin\Application\Commands\RejectProductCommand;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class RejectProductHandler
{
    public function handle(RejectProductCommand $command): ProductModel
    {
        $product = ProductModel::findOrFail($command->id);

        $product->update([
            'status'           => ProductStatusEnum::Rejected,
            'rejection_reason' => $command->reason,
        ]);

        if ($product->manager_id) {
            dispatch(new SendTelegramJob(
                role:    'manager',
                message: "❌ Mahsulotingiz rad etildi: {$product->name}. Sabab: {$command->reason}",
            ));
        }

        return $product->fresh()->load('manager');
    }
}
