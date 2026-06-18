<?php

namespace Modules\Auth\Services;

use App\Shared\Services\SMS\SmsService;
use Illuminate\Support\Facades\Redis;
use Modules\Auth\DTOs\SendOtpDTO;
use Modules\Auth\DTOs\VerifyOtpDTO;
use Modules\User\Models\User;

class AuthService
{
    private const OTP_TTL      = 120;
    private const OTP_LENGTH   = 6;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    public function sendOtp(SendOtpDTO $dto): void
    {
        $this->checkRateLimit($dto->phone, $dto->type->value);

        $code    = $this->generateCode();
        $key     = $this->otpKey($dto->phone, $dto->type->value);
        $message = "Tasdiqlash kodi: {$code}. " . self::OTP_TTL . " soniya ichida foydalaning.";

        Redis::connection()->setex($key, self::OTP_TTL, $code);

        $this->smsService->send($dto->phone, $message);
    }

    public function verifyOtp(VerifyOtpDTO $dto): array
    {
        $key         = $this->otpKey($dto->phone, $dto->type->value);
        $attemptsKey = $this->attemptsKey($dto->phone, $dto->type->value);

        $storedCode = Redis::connection()->get($key);

        if (! $storedCode) {
            throw new \Exception('OTP muddati tugagan yoki yuborilmagan.', 422);
        }

        $attempts = (int) Redis::connection()->get($attemptsKey);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Redis::connection()->del($key);
            throw new \Exception('Urinishlar soni oshib ketdi. Yangi kod so\'rang.', 429);
        }

        if ($storedCode !== $dto->code) {
            Redis::connection()->incr($attemptsKey);
            Redis::connection()->expire($attemptsKey, self::OTP_TTL);
            throw new \Exception('Kod noto\'g\'ri.', 422);
        }

        Redis::connection()->del($key);
        Redis::connection()->del($attemptsKey);

        $user = User::firstOrCreate(
            ['phone' => $dto->phone],
            ['name'  => null],
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function otpKey(string $phone, string $type): string
    {
        return "otp:{$type}:{$phone}";
    }

    private function attemptsKey(string $phone, string $type): string
    {
        return "otp_attempts:{$type}:{$phone}";
    }

    private function checkRateLimit(string $phone, string $type): void
    {
        $rateLimitKey = "otp_rate:{$type}:{$phone}";
        $count        = Redis::connection()->get($rateLimitKey);

        if ($count && (int) $count >= 3) {
            throw new \Exception('Juda ko\'p so\'rov. 10 daqiqadan keyin qayta urinib ko\'ring.', 429);
        }

        Redis::connection()->incr($rateLimitKey);
        Redis::connection()->expire($rateLimitKey, 600);
    }
}
