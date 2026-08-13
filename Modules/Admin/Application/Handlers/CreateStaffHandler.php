<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\Hash;
use Modules\Admin\Application\Commands\CreateStaffCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;

final class CreateStaffHandler
{
    public function handle(CreateStaffCommand $command): Staff
    {
        $dto = $command->dto;

        // Admin faqat menejer/kuryer yarata oladi; admin/super_admin — faqat super_admin.
        $actor = auth('sanctum')->user();
        if ($actor instanceof Staff
            && $actor->role === StaffRole::ADMIN
            && !in_array($dto->role, [StaffRole::SELLER, StaffRole::MANAGER, StaffRole::COURIER], true)) {
            abort(403, "Admin faqat menejer yoki kuryer yarata oladi");
        }

        if (Staff::where('email', $dto->email)->exists()) {
            abort(422, "Bu email allaqachon ro'yxatdan o'tgan");
        }

        $staff = Staff::create([
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => Hash::make($dto->password),
            'role'      => $dto->role,
            'is_active' => true,
        ]);

        if ($staff->role === StaffRole::COURIER) {
            CourierProfile::firstOrCreate(['courier_id' => $staff->id]);
        }

        return $staff->load('courierProfile');
    }
}
