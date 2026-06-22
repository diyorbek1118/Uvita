<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Repositories;

use Modules\Auth\Domain\Entities\OtpAttempt as OtpAttemptEntity;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Models\OtpAttempt as OtpAttemptModel;

final class EloquentOtpAttemptRepository implements OtpAttemptRepositoryInterface
{
    public function findActiveByPhone(string $phone): ?OtpAttemptEntity
    {
        $model = OtpAttemptModel::query()
            ->where('phone', $phone)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(OtpAttemptEntity $attempt): void
    {
        if ($attempt->id !== null) {
            OtpAttemptModel::where('id', $attempt->id)->update([
                'attempts_count' => $attempt->attemptsCount,
                'blocked_until'  => $attempt->blockedUntil,
                'is_verified'    => $attempt->isVerified,
            ]);
        } else {
            OtpAttemptModel::create([
                'phone'          => $attempt->phone,
                'code'           => $attempt->code,
                'type'           => 'login',
                'attempts_count' => $attempt->attemptsCount,
                'blocked_until'  => $attempt->blockedUntil,
                'expires_at'     => $attempt->expiresAt,
                'is_verified'    => $attempt->isVerified,
            ]);
        }
    }

    public function deleteByPhone(string $phone): void
    {
        OtpAttemptModel::where('phone', $phone)->delete();
    }

    private function toEntity(OtpAttemptModel $model): OtpAttemptEntity
    {
        return new OtpAttemptEntity(
            id:            $model->id,
            phone:         $model->phone,
            code:          $model->code,
            attemptsCount: $model->attempts_count,
            blockedUntil:  $model->blocked_until?->toDateTimeImmutable(),
            expiresAt:     $model->expires_at->toDateTimeImmutable(),
            isVerified:    $model->is_verified,
        );
    }
}
