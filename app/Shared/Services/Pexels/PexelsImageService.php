<?php

declare(strict_types=1);

namespace App\Shared\Services\Pexels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Pexels API orqali mahsulot/kategoriya uchun eng mos rasmlarni qidirib,
 * ularni public diskka yuklab oladi va URL qaytaradi.
 *
 * - Har bir qidiruv natijasi bitta run davomida keshlanadi (API limitini tejaydi).
 * - Bir xil query'dan foydalanadigan mahsulotlarga turli rasmlar taqsimlanadi.
 * - Xatolik yoki limit tugaganda bo'sh massiv qaytaradi (seeder davom etadi).
 */
final class PexelsImageService
{
    private const API_URL = 'https://api.pexels.com/v1/search';

    /** @var array<string, array<int, array<string, mixed>>> */
    private static array $searchCache = [];

    /** @var array<string, int> query → navbatdagi rasm indeksi */
    private static array $photoPointers = [];

    public function search(string $query, int $perPage = 8): array
    {
        $key = $this->cacheKey($query);

        if (isset(self::$searchCache[$key])) {
            return self::$searchCache[$key];
        }

        $apiKey = config('services.pexels.key');
        if (! $apiKey) {
            Log::warning('PexelsImageService: PEXELS_API_KEY sozlanmagan.');

            return self::$searchCache[$key] = [];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(20)
                ->get(self::API_URL, [
                    'query'       => $query,
                    'per_page'    => $perPage,
                    'orientation' => 'landscape',
                ]);

            // Rate limit (429) — 2 soniya kutib, bir marta qayta urinamiz
            if ($response->status() === 429) {
                Log::warning('PexelsImageService: rate limit (429), 2 soniya kutib qayta urinamiz.');
                usleep(2_000_000);

                $response = Http::withHeaders(['Authorization' => $apiKey])
                    ->timeout(20)
                    ->get(self::API_URL, [
                        'query'       => $query,
                        'per_page'    => $perPage,
                        'orientation' => 'landscape',
                    ]);
            }

            $photos = $response->successful() ? ($response->json('photos') ?? []) : [];

            if (! $response->successful()) {
                Log::warning("PexelsImageService: API javob berdi ({$response->status()}) — query: {$query}");
            }
        } catch (\Throwable $e) {
            Log::warning("PexelsImageService: xatolik ({$e->getMessage()}) — query: {$query}");
            $photos = [];
        }

        return self::$searchCache[$key] = $photos;
    }

    /**
     * Query uchun eng mos $count ta rasmni yuklab oladi va public URL'lar qaytaradi.
     *
     * @param  string  $query     Pexels qidiruv so'zi (inglizcha)
     * @param  string  $slug      Fayl nomi uchun asos
     * @param  int     $count     Qancha rasm kerak
     * @param  string  $directory products | categories
     * @param  bool    $square    Kategoriya uchun kvadrat (crop) rasm
     */
    public function downloadImages(string $query, string $slug, int $count = 2, string $directory = 'products', bool $square = false): array
    {
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $names = [
            $slug . '.jpg',
            $slug . '-2.jpg',
            $slug . '-3.jpg',
            $slug . '-4.jpg',
        ];

        // Barcha fayllar allaqachon mavjud bo'lsa — API'ga murojaat qilmaymiz
        // (Pexels limiti 200 so'rov/soat, qayta-seed qilishda shu yo'l bilan tejaladi).
        $existing = [];
        $missing  = false;

        for ($j = 0; $j < $count; $j++) {
            $path = $directory . '/' . $names[$j];

            if (Storage::disk('public')->exists($path)) {
                $existing[] = Storage::disk('public')->url($path);
            } else {
                $missing = true;
            }
        }

        if (! $missing) {
            return $existing;
        }

        $photos = $this->search($query);
        $total  = count($photos);

        if ($total === 0) {
            return $existing !== [] ? $existing : [];
        }

        $key     = $this->cacheKey($query);
        $pointer = self::$photoPointers[$key] ?? 0;
        $urls    = $existing;

        for ($j = 0; $j < min($count, $total); $j++) {
            $photo = $photos[($pointer + $j) % $total];
            $src   = $this->imageSource($photo, $square);

            if ($src === null) {
                continue;
            }

            $path = $directory . '/' . $names[$j];

            if (! Storage::disk('public')->exists($path)) {
                $saved = Http::timeout(60)->sink(Storage::disk('public')->path($path))->get($src)->successful();

                if (! $saved) {
                    Storage::disk('public')->delete($path);
                    Log::warning("PexelsImageService: rasm yuklab olinmadi — {$src}");

                    continue;
                }
            }

            $urls[] = Storage::disk('public')->url($path);
        }

        self::$photoPointers[$key] = ($pointer + $count) % max($total, 1);

        return array_values(array_unique($urls));
    }

    /**
     * Rasm manbasini tayyorlaydi: mahsulot uchun large, kategoriya uchun kvadrat crop.
     *
     * @param  array<string, mixed>  $photo
     */
    private function imageSource(array $photo, bool $square): ?string
    {
        $original = $photo['src']['original'] ?? null;

        if ($original === null) {
            return null;
        }

        if ($square) {
            return $original . '?auto=compress&cs=tinysrgb&fit=crop&w=600&h=600';
        }

        return $photo['src']['large'] ?? $original;
    }

    private function cacheKey(string $query): string
    {
        return strtolower(trim($query));
    }
}
