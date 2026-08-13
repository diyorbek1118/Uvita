<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Services;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryProof;

final class DeliveryConfirmationService
{
    public function ensureForOrder(int $orderId): DeliveryProof
    {
        $existing = DeliveryProof::where('order_id', $orderId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $pin = (string) random_int(1000, 9999);

        return DeliveryProof::create([
            'order_id' => $orderId,
            'pin_hash' => Hash::make($pin),
            'pin_encrypted' => Crypt::encryptString($pin),
        ]);
    }

    public function reveal(DeliveryProof $proof): string
    {
        return Crypt::decryptString($proof->pin_encrypted);
    }

    public function verify(
        int $orderId,
        int $courierId,
        string $pin,
        ?string $recipientName,
        ?float $latitude,
        ?float $longitude,
    ): DeliveryProof {
        $proof = DeliveryProof::query()->where('order_id', $orderId)->lockForUpdate()->firstOrFail();

        if ($proof->verified_at !== null) {
            throw new DomainException('Bu buyurtmaning yetkazish kodi avval tasdiqlangan.');
        }

        if ($proof->pin_locked_until?->isFuture()) {
            throw new DomainException(
                "Juda ko'p noto'g'ri kod kiritildi. {$proof->pin_locked_until->diffForHumans()} qayta urinib ko'ring."
            );
        }

        if (! Hash::check($pin, $proof->pin_hash)) {
            $attempts = $proof->pin_attempts + 1;
            $maxAttempts = (int) config('courier.delivery_pin.max_attempts', 5);
            $attributes = ['pin_attempts' => $attempts];

            if ($attempts >= $maxAttempts) {
                $attributes['pin_locked_until'] = now()->addMinutes(
                    (int) config('courier.delivery_pin.block_minutes', 10)
                );
                $attributes['pin_attempts'] = 0;
            }

            $proof->update($attributes);
            throw new DomainException('Yetkazish PIN kodi noto‘g‘ri.');
        }

        $proof->update([
            'pin_attempts' => 0,
            'pin_locked_until' => null,
            'recipient_name' => $recipientName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'verified_by_courier_id' => $courierId,
            'verified_at' => now(),
        ]);

        return $proof->fresh();
    }
}
