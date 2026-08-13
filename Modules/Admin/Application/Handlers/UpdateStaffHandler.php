<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Commands\UpdateStaffCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;

final class UpdateStaffHandler
{
    public function handle(UpdateStaffCommand $command): Staff
    {
        $staff = Staff::findOrFail($command->staffId);
        $dto   = $command->dto;

        // Admin faqat menejer/kuryerni tahrirlaydi va faqat menejer/kuryer roliga o'zgartiradi.
        $actor = auth('sanctum')->user();
        if ($actor instanceof Staff && $actor->role === StaffRole::ADMIN) {
            $managed = [StaffRole::SELLER, StaffRole::MANAGER, StaffRole::COURIER];
            if (!in_array($staff->role, $managed, true) || !in_array($dto->role, $managed, true)) {
                abort(403, "Admin faqat sotuvchi, menejer yoki kuryerni tahrirlashi mumkin");
            }
        }

        if ($staff->email !== $dto->email && Staff::where('email', $dto->email)->where('id', '!=', $staff->id)->exists()) {
            abort(422, 'Bu email allaqachon band');
        }

        $wasCourier = $staff->role === StaffRole::COURIER;
        $staff->update([
            'name'  => $dto->name,
            'email' => $dto->email,
            'role'  => $dto->role,
        ]);

        if ($dto->role === StaffRole::COURIER) {
            CourierProfile::firstOrCreate(['courier_id' => $staff->id]);
        } elseif ($wasCourier) {
            CourierProfile::where('courier_id', $staff->id)->delete();
        }

        return $staff->fresh()->load('courierProfile');
    }
}
