<?php

declare(strict_types=1);

namespace App\Shared\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Firebase Cloud Messaging (HTTP v1) — service account orqali.
 *
 * Sozlash: .env da FIREBASE_SERVICE_ACCOUNT_JSON (service account JSON
 * kontenti yoki fayl yo'li) ko'rsatiladi. Bo'lmasa — push yuborilmaydi
 * (lokal bildirishnoma + polling ishlashda davom etadi).
 */
final class FcmService
{
    private const TOKEN_CACHE_KEY   = 'fcm_oauth_access_token';
    private const TOKEN_CACHE_TTL   = 3300; // 55 daqiqa (Google 1 soat beradi)

    public function configured(): bool
    {
        return $this->serviceAccount() !== null;
    }

    /**
     * Data-only xabar yuborish. Muvaffaqiyat yoki sozlanmagan → true;
     * tarmoq/sozlamalardagi xato → false (log'ga tushadi, buyurtma buzilmaydi).
     *
     * @param  array<string, scalar>  $data
     */
    public function send(string $token, array $data): bool
    {
        $sa = $this->serviceAccount();
        if ($sa === null) {
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken($sa))
                ->timeout(5) // sinxron chaqiruv — buyurtma javobini osiltirmaslik
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$sa['project_id']}/messages:send",
                    [
                        'message' => [
                            'token' => $token,
                            'data'  => array_map('strval', $data),
                            // Notification payload — tizim O'ZI ko'rsatadi, shuning uchun
                            // app butunlay yopiq bo'lsa ham xabar keladi (data-only
                            // xabarlar ba'zi qurilmalarda background job bloklanishi
                            // tufayli ko'rinmay qolishi mumkin).
                            'notification' => [
                                'title' => $data['title'] ?? 'Yangi buyurtma',
                                'body'  => $data['body'] ?? '',
                            ],
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id'     => 'new_orders',
                                    'icon'           => '@mipmap/ic_launcher',
                                    'color'          => '#0A2B1D',
                                    'notification_count' => 1,
                                ],
                            ],
                        ],
                    ]
                );

            if (!$response->successful()) {
                report(new \RuntimeException(
                    'FCM yuborish xatosi: ' . $response->status() . ' ' . $response->body()
                ));
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /** @return array{project_id: string, client_email: string, private_key: string}|null */
    private function serviceAccount(): ?array
    {
        $raw = config('services.firebase.service_account_json');
        if (empty($raw)) {
            return null;
        }

        // Fayl yo'li yoki JSON kontent
        if (is_file($raw)) {
            $raw = (string) file_get_contents($raw);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($data)
            || !isset($data['project_id'], $data['client_email'], $data['private_key'])) {
            return null;
        }

        return $data;
    }

    private function accessToken(array $sa): string
    {
        return Cache::remember(
            self::TOKEN_CACHE_KEY,
            self::TOKEN_CACHE_TTL,
            fn (): string => $this->fetchAccessToken($sa)
        );
    }

    private function fetchAccessToken(array $sa): string
    {
        $response = Http::asForm()->timeout(5)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $this->createJwt($sa),
        ]);

        $json = $response->json();
        if (!is_array($json) || empty($json['access_token'])) {
            throw new \RuntimeException('FCM OAuth token olinmadi: ' . $response->body());
        }

        return $json['access_token'];
    }

    private function createJwt(array $sa): string
    {
        $now = time();

        $header = $this->base64Url(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $claims = $this->base64Url(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $signingInput = $header . '.' . $claims;

        openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
