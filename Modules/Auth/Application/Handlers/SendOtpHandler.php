<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Jobs\SendSmsJob;
use DateTimeImmutable;
use Modules\Auth\Application\Commands\SendOtpCommand;
use Modules\Auth\Domain\Entities\OtpAttempt;
use Modules\Auth\Domain\Exceptions\OtpRateLimitException;
use App\Shared\Services\Settings\SettingService;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class SendOtpHandler
{
    private const OTP_LENGTH = 6;

    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly SettingService                $settingService,
    ) {}

    public function handle(SendOtpCommand $command): void
    {
        // 1. PhoneNumber VO formatni tekshiradi
        $phone = new PhoneNumber($command->dto->phone);

        // 2. Mavjud active OTP bormi tekshir
        $existing = $this->otpRepository->findActiveByPhone($phone->value);

        if ($existing !== null) {
            // 3. Bloklangan bo'lsa — foydalanuvchini xabardor qil
            if ($existing->isBlocked()) {
                throw new OtpRateLimitException($existing->blockedUntil);
            }

            // 3. Muddati o'tmagan OTP mavjud — qayta yuborma
            if (! $existing->isExpired()) {
                return;
            }
        }

        // 4. 6 xonali random kod
        $code = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $ttl = $this->settingService->otpExpirySeconds();

        // 5. OtpAttempt entity yaratish
        $attempt = OtpAttempt::create(
            phone:     $phone->value,
            code:      $code,
            expiresAt: new DateTimeImmutable("+{$ttl} seconds"),
        );

        // 6. Repository orqali saqlash
        $this->otpRepository->save($attempt);

        // 7. SMS ni queue orqali async yuborish
        $message = "Tasdiqlash kodi: {$code}. {$ttl} soniya ichida foydalaning.";
        
        \Illuminate\Support\Facades\Log::channel('single')->info("OTP yuborildi - Raqam: {$phone->value}, Kod: {$code}");
        
        dispatch(new SendSmsJob($phone->value, $message));
    }
}
