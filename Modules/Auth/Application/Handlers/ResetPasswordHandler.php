<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\ResetPasswordCommand;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class ResetPasswordHandler
{
    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    /**
     * @return array{token: string, user: UserEntity}
     */
    public function handle(ResetPasswordCommand $command): array
    {
        $phone = new PhoneNumber($command->dto->phone);

        // 1. OTP avval /auth/otp/confirm orqali tasdiqlangan bo'lishi kerak
        if ($this->otpRepository->findVerifiedByPhone($phone->value) === null) {
            throw new InvalidOtpException('Tasdiqlash kodining muddati tugagan. Kodni qayta yuboring.');
        }

        // 2. Foydalanuvchi mavjud bo'lishi kerak
        $user = $this->userRepository->findByPhone($phone->value);
        if ($user === null) {
            throw new DomainException("Bu raqam ro'yxatdan o'tmagan.");
        }

        // 3. Yangi parolni o'rnatish
        $user = $this->userRepository->save(
            $user->withPassword(Hash::make($command->dto->password))
        );

        // 4. Token generatsiya (avtomatik kirish)
        $token = $this->tokenService->createForUser($user);

        return ['token' => $token, 'user' => $user];
    }
}
