<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use App\Shared\Services\Settings\SettingService;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\OtpRateLimitException;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class OtpVerifier
{
    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly SettingService                $settingService,
    ) {}

    /**
     * OTP'ni tekshiradi va tasdiqlangan deb belgilaydi.
     *
     * @throws InvalidOtpException|OtpRateLimitException
     */
    public function verify(string $phone, string $code): void
    {
        // 1. PhoneNumber VO formatni tekshiradi
        $phone = new PhoneNumber($phone);

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
        if (! $attempt->isValid($code)) {
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
    }
}
