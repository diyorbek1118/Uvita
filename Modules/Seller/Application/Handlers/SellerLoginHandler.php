<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Handlers;

use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

final class SellerLoginHandler
{
    public function handle(string $phone, string $password): array
    {
        $seller = Staff::query()->where('phone', $phone)->where('role', StaffRole::SELLER)->first();

        if ($seller === null || ! Hash::check($password, $seller->password)) {
            abort(401, 'Telefon raqam yoki parol noto‘g‘ri.');
        }
        if (! $seller->is_active) {
            abort(403, 'Seller akkaunti faol emas. Adminga murojaat qiling.');
        }

        $expiresAt = now()->addHours((int) config('auth.staff_token_expiration_hours', 12));

        return [
            'token' => $seller->createToken('seller', ['*'], $expiresAt)->plainTextToken,
            'staff' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'phone' => $seller->phone,
                'email' => $seller->email,
                'role' => $seller->role->value,
            ],
            'shops' => SellerProfileResource::collection(
                $seller->sellerProfiles()->where('is_active', true)->oldest()->get()
            )->resolve(),
        ];
    }
}
