<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use DateTimeImmutable;
use Modules\Auth\Application\Commands\ConfirmOtpCommand;
use Modules\Auth\Application\Services\OtpVerifier;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class ConfirmOtpHandler
{
    /** Tasdiqlangan OTP shu vaqtgacha amal qiladi — qolgan bosqichlarni to'ldirish uchun yetarli */
    private const CONFIRMED_TTL = '+15 minutes';

    public function __construct(
        private readonly OtpVerifier $otpVerifier,
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(ConfirmOtpCommand $command): void
    {
        $phone = new PhoneNumber($command->dto->phone);

        // 1. OTP'ni tekshiradi va tasdiqlangan deb belgilaydi
        $this->otpVerifier->verify($phone->value, $command->dto->code);

        // 2. Tasdiqlangan OTP muddatini uzaytiramiz — foydalanuvchi parol,
        //    ism, manzil kabi bosqichlarni to'ldirguncha kod "tugab qolmasligi" uchun
        $verified = $this->otpRepository->findVerifiedByPhone($phone->value);
        if ($verified !== null) {
            $this->otpRepository->save(
                $verified->extendExpiry(new DateTimeImmutable(self::CONFIRMED_TTL))
            );
        }

        // 3. Maqsad bo'yicha foydalanuvchi holatini tekshirish
        $existingUser = $this->userRepository->findByPhone($phone->value);
        if (in_array($command->dto->purpose, ['register', 'change_phone'], true) && $existingUser !== null) {
            throw new DomainException("Bu raqam allaqachon ro'yxatdan o'tgan.");
        }
        if ($command->dto->purpose === 'reset' && $existingUser === null) {
            throw new DomainException("Bu raqam ro'yxatdan o'tmagan.");
        }
    }
}
