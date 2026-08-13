<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class FirebaseCloudMessagingService
{
    /** @param array<string, mixed> $data */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $projectId = (string) config('courier.fcm.project_id');
        if ($projectId === '' || (string) config('courier.fcm.client_email') === '' || (string) config('courier.fcm.private_key') === '') {
            Log::info('FCM sozlanmagan, push yuborilmadi', ['title' => $title]);

            return false;
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $this->stringifyData($data),
                    'webpush' => ['headers' => ['Urgency' => 'high']],
                    'android' => ['priority' => 'high'],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::warning('FCM push xatosi', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return false;
    }

    private function accessToken(): string
    {
        return Cache::remember('courier_fcm_access_token', now()->addMinutes(50), function (): string {
            $issuedAt = time();
            $tokenUri = (string) config('courier.fcm.token_uri', 'https://oauth2.googleapis.com/token');
            $assertion = $this->signedJwt([
                'iss' => (string) config('courier.fcm.client_email'),
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $issuedAt,
                'exp' => $issuedAt + 3600,
            ]);

            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])->throw();

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('FCM access token olinmadi.');
            }

            return $token;
        });
    }

    /** @param array<string, int|string> $claims */
    private function signedJwt(array $claims): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$payload;
        $privateKey = str_replace('\\n', "\n", (string) config('courier.fcm.private_key'));

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('FCM JWT imzolanmadi.');
        }

        return $unsigned.'.'.$this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function stringifyData(array $data): array
    {
        return collect($data)->mapWithKeys(fn (mixed $value, string $key): array => [
            $key => is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_THROW_ON_ERROR),
        ])->all();
    }
}
