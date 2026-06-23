<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\Hash;
use Modules\Admin\Application\Commands\CreateStaffCommand;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class CreateStaffHandler
{
    public function handle(CreateStaffCommand $command): Staff
    {
        $dto = $command->dto;

        if (Staff::where('email', $dto->email)->exists()) {
            abort(422, "Bu email allaqachon ro'yxatdan o'tgan");
        }

        return Staff::create([
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => Hash::make($dto->password),
            'role'      => $dto->role,
            'is_active' => true,
        ]);
    }
}
