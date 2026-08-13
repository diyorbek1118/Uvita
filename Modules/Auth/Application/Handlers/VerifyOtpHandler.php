<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use Modules\Auth\Application\Commands\VerifyOtpCommand;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Application\Services\OtpVerifier;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class VerifyOtpHandler
{
    public function __construct(
        private readonly OtpVerifier                  $otpVerifier,
        private readonly UserRepositoryInterface       $userRepository,
        private readonly TokenServiceInterface         $tokenService,
    ) {}

    /**
     * @return array{token: string, user: UserEntity, is_new: bool}
     */
    public function handle(VerifyOtpCommand $command): array
    {
        // 1. OTP'ni tekshiradi (umumiy servis)
        $phone = new PhoneNumber($command->dto->phone);
        $this->otpVerifier->verify($phone->value, $command->dto->code);

        // 2. Foydalanuvchi topish yoki yangi yaratish
        $userEntity = $this->userRepository->findByPhone($phone->value);
        $isNew      = $userEntity === null;

        if ($isNew) {
            $userEntity = $this->userRepository->save(UserEntity::create($phone->value));
        }

        // 3. Sanctum token generatsiya
        $token = $this->tokenService->createForUser($userEntity);

        // 4. Natija
        return [
            'token'  => $token,
            'user'   => $userEntity,
            'is_new' => $isNew,
        ];
    }
}
