<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Commands\DeleteStaffCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class DeleteStaffHandler
{
    public function handle(DeleteStaffCommand $command): void
    {
        $staff = Staff::findOrFail($command->staffId);

        if ($staff->role === StaffRole::SUPER_ADMIN) {
            abort(422, "Bosh adminni o'chirib bo'lmaydi");
        }

        DB::transaction(function () use ($staff): void {
            Product::withTrashed()
                ->where('manager_id', $staff->id)
                ->update(['manager_id' => null]);

            OrderModel::where('courier_id', $staff->id)
                ->update(['courier_id' => null]);

            $staff->tokens()->delete();
            $staff->delete();
        });
    }
}
