<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\Hash;
use Modules\Admin\Application\DTOs\StaffLoginDTO;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class StaffLoginHandler
{
    public function handle(StaffLoginDTO $dto): array
    {
        $staff = Staff::where('email', $dto->email)->first();

        if ($staff === null || ! Hash::check($dto->password, $staff->password)) {
            abort(401, "Email yoki parol noto'g'ri.");
        }

        if (! $staff->is_active) {
            abort(403, 'Akkaunt faol emas. Adminga murojaat qiling.');
        }

        $expiresAt = now()->addHours((int) config('auth.staff_token_expiration_hours', 12));
        $token = $staff->createToken($staff->role->value, ['*'], $expiresAt)->plainTextToken;

        return [
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role->value,
            ],
            'token' => $token,
        ];
    }
}
