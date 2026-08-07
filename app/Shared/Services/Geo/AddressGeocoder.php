<?php

declare(strict_types=1);

namespace App\Shared\Services\Geo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Manzil → koordinatalar (lat/lng).
 *
 * Nominatim (OSM) dan foydalanadi, lekin natijalar address_geocodes jadvalida
 * saqlanadi — bir xil manzil uchun tashqi xizmatga qayta so'rov ketmaydi.
 *
 * Kesh qoidalari:
 *  - Xit (koordinata topildi) → keshlanadi.
 *  - "Topilmadi" (xizmat normal javob qaytardi, lekin natija yo'q) → keshlanadi
 *    (manzilni qayta-qayta so'rab turmaslik uchun).
 *  - Xato (tarmoq/5xx/timeout) → keshlanmaydi — keyingi buyurtmada qayta uriniladi.
 */
final class AddressGeocoder
{
    /**
     * Manzil matni uchun koordinatalarni qaytaradi (topilmasa null).
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $query): ?array
    {
        if (trim($query) === '') {
            return null;
        }

        $cached = DB::table('address_geocodes')->where('query', $query)->first();
        if ($cached) {
            return $cached->lat !== null
                ? ['lat' => (float) $cached->lat, 'lng' => (float) $cached->lng]
                : null;
        }

        $result = $this->fetchFromNominatim($query);

        // Xato → keshlanmaydi, keyingi safar qayta uriniladi.
        if ($result === null) {
            return null;
        }

        $coords = ($result['lat'] ?? null) !== null
            ? ['lat' => (float) $result['lat'], 'lng' => (float) $result['lng']]
            : null;

        // updateOrInsert — bir vaqtda kelgan bir xil so'rovlar uchun xavfsiz.
        DB::table('address_geocodes')->updateOrInsert(
            ['query' => $query],
            [
                'lat'        => $coords['lat'] ?? null,
                'lng'        => $coords['lng'] ?? null,
                'updated_at' => now(),
            ]
        );

        return $coords;
    }

    /**
     * Ma'lum bo'lgan koordinatani keshlaydi (masalan mijoz oldindan
     * geokodlagan bo'lsa) — keyingi buyurtmalar uchun saqlanadi.
     */
    public function remember(string $query, float $lat, float $lng): void
    {
        if (trim($query) === '') {
            return;
        }

        DB::table('address_geocodes')->updateOrInsert(
            ['query' => $query],
            ['lat' => $lat, 'lng' => $lng, 'updated_at' => now()]
        );
    }

    /**
     * Nominatim'dan natija:
     *  - ['lat'=>float,'lng'=>float] → topildi
     *  - []                          → topilmadi (keshlanadi)
     *  - null                        → xato (keshlanmaydi)
     *
     * @return array{lat: float, lng: float}|array{}|null
     */
    private function fetchFromNominatim(string $query): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => (string) config('services.nominatim.user_agent')])
                ->get((string) config('services.nominatim.url'), [
                    'format' => 'jsonv2',
                    'limit'  => 1,
                    'q'      => $query,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $hit = $response->json()[0] ?? null;
            if (! is_array($hit) || ! isset($hit['lat'], $hit['lon'])) {
                return [];
            }

            return ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon']];
        } catch (\Throwable) {
            return null;
        }
    }
}
