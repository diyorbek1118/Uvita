<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\RegisterCommand;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class RegisterHandler
{
    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    /**
     * @return array{token: string, user: UserEntity}
     */
    public function handle(RegisterCommand $command): array
    {
        $phone = new PhoneNumber($command->dto->phone);

        // 1. OTP avval /auth/otp/confirm orqali tasdiqlangan bo'lishi kerak
        if ($this->otpRepository->findVerifiedByPhone($phone->value) === null) {
            throw new InvalidOtpException('Tasdiqlash kodining muddati tugagan. Kodni qayta yuboring.');
        }

        // 2. Raqam oldin ro'yxatdan o'tmagan bo'lishi kerak
        if ($this->userRepository->findByPhone($phone->value) !== null) {
            throw new DomainException("Bu raqam allaqachon ro'yxatdan o'tgan.");
        }

        // 3. Yangi foydalanuvchi yaratish (profil + parol)
        $user = UserEntity::create(
            phone: $phone->value,
            name: $command->dto->name,
            surname: $command->dto->surname,
            region: $command->dto->region,
            district: $command->dto->district,
            address: $command->dto->address,
            lat: $command->dto->lat,
            lng: $command->dto->lng,
            password: Hash::make($command->dto->password),
        );

        try {
            $user = $this->userRepository->save($user);
        } catch (QueryException $e) {
            // Parallel so'rovda ikkalasi ham tekshiruvdan o'tib ketsa — unique constraint
            // SQLSTATE 23000 (driver'dan mustaqil) yoki MySQL indeks nomi orqali aniqlaymiz
            if ((string) $e->getCode() === '23000' || str_contains($e->getMessage(), 'users_phone_unique')) {
                throw new DomainException("Bu raqam allaqachon ro'yxatdan o'tgan.");
            }
            throw $e;
        }

        // 4. Token generatsiya
        $token = $this->tokenService->createForUser($user);

        return ['token' => $token, 'user' => $user];
    }
}
