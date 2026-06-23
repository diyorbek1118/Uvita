<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Commands\UpdateStaffCommand;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class UpdateStaffHandler
{
    public function handle(UpdateStaffCommand $command): Staff
    {
        $staff = Staff::findOrFail($command->staffId);
        $dto   = $command->dto;

        if ($staff->email !== $dto->email && Staff::where('email', $dto->email)->where('id', '!=', $staff->id)->exists()) {
            abort(422, 'Bu email allaqachon band');
        }

        $staff->update([
            'name'  => $dto->name,
            'email' => $dto->email,
            'role'  => $dto->role,
        ]);

        return $staff->fresh();
    }
}
