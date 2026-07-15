<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Modules\Admin\Application\Commands\ApproveProductCommand;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class ApproveProductHandler
{
    public function handle(ApproveProductCommand $command): ProductModel
    {
        $product = ProductModel::findOrFail($command->id);

        // Moderatsiyada (inactive) yoki bloklangan (rejected) mahsulotni tasdiqlash mumkin.
        if ($product->status === ProductStatusEnum::Active) {
            abort(422, 'Mahsulot allaqachon faol');
        }

        $product->update([
            'status'           => ProductStatusEnum::Active,
            'rejection_reason' => null,
        ]);

        if ($product->manager_id) {
            dispatch(new SendTelegramJob(
                role:    'manager',
                message: "✅ Mahsulotingiz tasdiqlandi: {$product->name}",
            ));
        }

        return $product->fresh()->load('manager');
    }
}
