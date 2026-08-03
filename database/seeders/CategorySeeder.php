<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Services\Pexels\PexelsImageService;
use Illuminate\Database\Seeder;
use Modules\Category\Infrastructure\Persistence\Models\Category;

/**
 * Oziq-ovqat bozori kategoriyalari (fast-food emas — kundalik mahsulotlar).
 * Har bir kategoriya uchun Pexels'dan eng mos kvadrat rasm yuklab olinadi.
 */
class CategorySeeder extends Seeder
{
    /**
     * name, slug, pexels (qidiruv so'zi)
     *
     * @var array<int, array{name: string, slug: string, pexels: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Sabzavotlar',                 'slug' => 'sabzavotlar',              'pexels' => 'fresh vegetables'],
        ['name' => 'Mevalar',                     'slug' => 'mevalar',                  'pexels' => 'fresh fruits'],
        ['name' => 'Don va dukkaklilar',          'slug' => 'don-va-dukkaklilar',       'pexels' => 'grains and legumes'],
        ['name' => "Yog'lar",                     'slug' => 'yog-lar',                  'pexels' => 'cooking oil bottle'],
        ['name' => 'Sut mahsulotlari va tuxum',   'slug' => 'sut-mahsulotlari',         'pexels' => 'dairy products milk eggs'],
        ['name' => 'Shakar, un va pishiriq',      'slug' => 'shakar-un',                'pexels' => 'flour sugar baking'],
        ['name' => 'Ziravorlar',                  'slug' => 'ziravorlar',               'pexels' => 'spices kitchen'],
        ['name' => 'Ichimliklar',                 'slug' => 'ichimliklar',              'pexels' => 'beverages drinks'],
        ['name' => 'Konserva va tayyor mahsulotlar', 'slug' => 'konserva',              'pexels' => 'canned food'],
        ['name' => "Go'sht, parranda va baliq",   'slug' => 'gosht-parranda',           'pexels' => 'fresh meat'],
    ];

    public function run(): void
    {
        $service = app(PexelsImageService::class);

        foreach (self::CATEGORIES as $index => $category) {
            $image = $service->downloadImages($category['pexels'], $category['slug'], 1, 'categories', square: true);

            Category::create([
                'name'      => $category['name'],
                'slug'      => $category['slug'],
                'image'     => $image[0] ?? null,
                'parent_id' => null,
                'is_active' => true,
            ]);

            $this->command?->info("✓ Kategoriya: {$category['name']}" . (empty($image) ? ' (rasmsiz)' : ''));
        }
    }
}
