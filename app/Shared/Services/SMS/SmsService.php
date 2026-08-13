<?php

declare(strict_types=1);

namespace App\Shared\Services\SMS;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;

final class SmsService
{
    public function send(string $phone, string $message): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $driver = (string) config('sms.driver', 'log');

        if ($driver === 'log') {
            if (app()->environment('production')) {
                throw new LogicException('Production muhitida SMS_DRIVER=log ishlatib bo‘lmaydi.');
            }

            Log::info('[SMS MOCK]', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return;
        }

        match ($driver) {
            'eskiz' => $this->sendViaEskiz($phone, $message),
            'http' => $this->sendViaHttpGateway($phone, $message),
            default => throw new LogicException("Noma'lum SMS driver: {$driver}"),
        };
    }

    private function sendViaEskiz(string $phone, string $message): void
    {
        $email = (string) config('sms.eskiz.email', '');
        $password = (string) config('sms.eskiz.password', '');
        $baseUrl = (string) config('sms.eskiz.base_url', '');

        if ($email === '' || $password === '' || $baseUrl === '') {
            throw new LogicException('Eskiz SMS credentials to‘liq sozlanmagan.');
        }

        $payload = [
            'mobile_phone' => ltrim($phone, '+'),
            'message' => $message,
            'from' => (string) config('sms.from', '4546'),
        ];

        $callbackUrl = config('sms.eskiz.callback_url');
        if (is_string($callbackUrl) && $callbackUrl !== '') {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = $this->eskizRequest($baseUrl, $email, $password)
            ->asForm()
            ->post("{$baseUrl}/message/sms/send", $payload);

        if ($response->unauthorized()) {
            Cache::forget('sms.eskiz.token');
            $response = $this->eskizRequest($baseUrl, $email, $password)
                ->asForm()
                ->post("{$baseUrl}/message/sms/send", $payload);
        }

        $response->throw();
    }

    private function eskizRequest(string $baseUrl, string $email, string $password): PendingRequest
    {
        $token = Cache::remember('sms.eskiz.token', now()->addMinutes(50), function () use ($baseUrl, $email, $password): string {
            $response = Http::asForm()
                ->timeout(15)
                ->retry(2, 500)
                ->post("{$baseUrl}/auth/login", [
                    'email' => $email,
                    'password' => $password,
                ]);

            $response->throw();

            $token = (string) $response->json('data.token', '');
            if ($token === '') {
                throw new LogicException('Eskiz SMS token qaytarmadi.');
            }

            return $token;
        });

        return Http::withToken($token)
            ->timeout(15)
            ->retry(2, 500);
    }

    private function sendViaHttpGateway(string $phone, string $message): void
    {
        $url = (string) config('sms.http.url', '');
        if ($url === '') {
            throw new LogicException('SMS_HTTP_URL sozlanmagan.');
        }

        $request = Http::acceptJson()
            ->timeout(15)
            ->retry(2, 500);

        $token = (string) config('sms.http.token', '');
        if ($token !== '') {
            $header = (string) config('sms.http.token_header', 'Authorization');
            $prefix = trim((string) config('sms.http.token_prefix', 'Bearer'));
            $request = $request->withHeader(
                $header,
                $prefix === '' ? $token : "{$prefix} {$token}"
            );
        }

        $payload = [
            (string) config('sms.http.phone_field', 'phone') => $phone,
            (string) config('sms.http.message_field', 'message') => $message,
        ];

        $from = (string) config('sms.from', '');
        $fromField = (string) config('sms.http.from_field', 'from');
        if ($from !== '' && $fromField !== '') {
            $payload[$fromField] = $from;
        }

        $request->post($url, $payload)->throw();
    }
}
