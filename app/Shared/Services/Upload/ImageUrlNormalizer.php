<?php

declare(strict_types=1);

namespace App\Shared\Services\Upload;

/**
 * Mahsulot rasm URL'larini hozirgi so'rov host'iga moslaydi.
 *
 * DB'da eski rasmlar `http://localhost:8000/storage/...` ko'rinishida saqlangan.
 * ngrok/static domain orqali kirilganda bu URL ishlamaydi — normalizer
 * localhost URL'larini hozirgi request'ning scheme+host'iga almashtiradi.
 */
final class ImageUrlNormalizer
{
    /**
     * @param  array<int, string>|string|null  $urls
     * @return array<int, string>
     */
    public static function normalizeArray(array|string|null $urls): array
    {
        if ($urls === null) {
            return [];
        }

        // Ba'zi eski modellarda images JSON-string bo'lib qolgan bo'lishi mumkin
        if (is_string($urls)) {
            $decoded = json_decode($urls, true);
            $urls = is_array($decoded) ? $decoded : [$urls];
        }

        return array_map(
            static fn (string $url): string => self::normalize($url),
            $urls,
        );
    }

    public static function normalize(string $url): string
    {
        // localhost / 127.0.0.1 / eski ngrok dinamik domainlari bilan
        // boshlangan URL'larni hozirgi request host'i bilan almashtiramiz.
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?/#i', $url) ||
            preg_match('#^https?://[a-z0-9-]+\.ngrok-free\.app/#i', $url)) {
            $parts = parse_url($url);
            $path  = $parts['path'] ?? '/';

            // query string (masalan ?v=123) yo'qolmasligi uchun saqlab qolamiz
            if (isset($parts['query'])) {
                $path .= '?' . $parts['query'];
            }

            return url($path);
        }

        return $url;
    }
}
