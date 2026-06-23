<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Services\Settings\SettingService;
use Modules\Auth\Application\Commands\VerifyOtpCommand;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\OtpRateLimitException;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class VerifyOtpHandler
{
    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface       $userRepository,
        private readonly TokenServiceInterface         $tokenService,
        private readonly SettingService                $settingService,
    ) {}

    /**
     * @return array{token: string, user: UserEntity, is_new: bool}
     */
    public function handle(VerifyOtpCommand $command): array
    {
        // 1. PhoneNumber VO formatni tekshiradi
        $phone = new PhoneNumber($command->dto->phone);

        // 2. Active OTP ni topadi
        $attempt = $this->otpRepository->findActiveByPhone($phone->value);

        if ($attempt === null) {
            throw new InvalidOtpException();
        }

        // 3. Bloklangan bo'lsa
        if ($attempt->isBlocked()) {
            throw new OtpRateLimitException($attempt->blockedUntil);
        }

        // 4. Muddati o'tgan bo'lsa
        if ($attempt->isExpired()) {
            throw new InvalidOtpException("OTP muddati tugagan.");
        }

        // 5. Kod noto'g'ri bo'lsa — urinishni oshir
        if (! $attempt->isValid($command->dto->code)) {
            $attempt->incrementAttempts(
                $this->settingService->otpMaxAttempts(),
                $this->settingService->otpBlockMinutes(),
            );
            $this->otpRepository->save($attempt);

            if ($attempt->isBlocked()) {
                throw new OtpRateLimitException($attempt->blockedUntil);
            }

            throw new InvalidOtpException("OTP kod noto'g'ri.");
        }

        // 6. Tasdiqlangan deb belgilash
        $attempt->markAsVerified();
        $this->otpRepository->save($attempt);

        // 7. Foydalanuvchi topish yoki yangi yaratish
        $userEntity = $this->userRepository->findByPhone($phone->value);
        $isNew      = $userEntity === null;

        if ($isNew) {
            $userEntity = $this->userRepository->save(UserEntity::create($phone->value));
        }

        // 8. Sanctum token generatsiya
        $token = $this->tokenService->createForUser($userEntity);

        // 9. Natija
        return [
            'token'  => $token,
            'user'   => $userEntity,
            'is_new' => $isNew,
        ];
    }
}
