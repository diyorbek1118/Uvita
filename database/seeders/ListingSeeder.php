<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Services\Pexels\PexelsImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Dehqon Bozor — e'lonlar (C2C). Dehqonlar o'z hosilini narxlab qo'yadi.
 * Har bir e'lon Pexels'dan mos rasm bilan.
 */
class ListingSeeder extends Seeder
{
    /**
     * title, category (slug), pexels, price, quantity, unit, region, district, status
     *
     * @var array<int, array<string, mixed>>
     */
    private const LISTINGS = [
        ['title' => 'Qizil Kartoshka (yangi hosil)', 'category' => 'sabzavotlar', 'pexels' => 'red potatoes harvest', 'price' => 3500, 'quantity' => 2000, 'unit' => 'kg', 'region' => 'Sirdaryo', 'district' => 'Sirdaryo', 'status' => 'active'],
        ['title' => 'Shirin Sabzi', 'category' => 'sabzavotlar', 'pexels' => 'fresh carrots', 'price' => 2800, 'quantity' => 1500, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Chinoz', 'status' => 'active'],
        ['title' => 'Sarig\' Piyoz', 'category' => 'sabzavotlar', 'pexels' => 'golden onions', 'price' => 4200, 'quantity' => 800, 'unit' => 'kg', 'region' => 'Jizzax', 'district' => 'G\'allaorol', 'status' => 'active'],
        ['title' => 'Yangi Uzilgan Pomidor (Yusupov navi)', 'category' => 'sabzavotlar', 'pexels' => 'red tomatoes', 'price' => 12000, 'quantity' => 250, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Parkent', 'status' => 'active'],
        ['title' => 'Bulg\'or Qalampiri', 'category' => 'sabzavotlar', 'pexels' => 'bell peppers', 'price' => 14000, 'quantity' => 320, 'unit' => 'kg', 'region' => 'Samarqand', 'district' => 'Samarqand', 'status' => 'active'],
        ['title' => 'Semerenko Olma', 'category' => 'mevalar', 'pexels' => 'green apples', 'price' => 8000, 'quantity' => 1500, 'unit' => 'kg', 'region' => 'Namangan', 'district' => 'Chust', 'status' => 'active'],
        ['title' => 'Qizil Olma (selektiv)', 'category' => 'mevalar', 'pexels' => 'red apples', 'price' => 9500, 'quantity' => 900, 'unit' => 'kg', 'region' => 'Farg\'ona', 'district' => 'Rishton', 'status' => 'active'],
        ['title' => 'Katta Oq Uzum', 'category' => 'mevalar', 'pexels' => 'white grapes', 'price' => 15000, 'quantity' => 600, 'unit' => 'kg', 'region' => 'Samarqand', 'district' => 'Bulung\'ur', 'status' => 'active'],
        ['title' => 'Shaftoli (Toshkent navi)', 'category' => 'mevalar', 'pexels' => 'peaches', 'price' => 20000, 'quantity' => 400, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Boka', 'status' => 'pending'],
        ['title' => 'Anor (shirin)', 'category' => 'mevalar', 'pexels' => 'pomegranate', 'price' => 22000, 'quantity' => 300, 'unit' => 'kg', 'region' => 'Surxondaryo', 'district' => 'Sherobod', 'status' => 'active'],
        ['title' => 'Alanga Guruch', 'category' => 'don-va-dukkaklilar', 'pexels' => 'white rice', 'price' => 26000, 'quantity' => 3000, 'unit' => 'kg', 'region' => 'Andijon', 'district' => 'Qo\'rg\'ontepa', 'status' => 'active'],
        ['title' => 'Devzira Guruch', 'category' => 'don-va-dukkaklilar', 'pexels' => 'rice grains', 'price' => 34000, 'quantity' => 1200, 'unit' => 'kg', 'region' => 'Farg\'ona', 'district' => 'Buvayda', 'status' => 'active'],
        ['title' => 'Sariq Mosh', 'category' => 'don-va-dukkaklilar', 'pexels' => 'mung beans', 'price' => 21000, 'quantity' => 750, 'unit' => 'kg', 'region' => 'Andijon', 'district' => 'Xo\'jaobod', 'status' => 'active'],
        ['title' => 'Oq Karam', 'category' => 'sabzavotlar', 'pexels' => 'cabbage', 'price' => 4500, 'quantity' => 1000, 'unit' => 'kg', 'region' => 'Buxoro', 'district' => 'Buxoro', 'status' => 'active'],
        ['title' => 'Bodring (issiqxona)', 'category' => 'sabzavotlar', 'pexels' => 'cucumbers', 'price' => 11000, 'quantity' => 280, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Qibray', 'status' => 'rejected'],
        ['title' => 'Bodring (yangi uzilgan)', 'category' => 'sabzavotlar', 'pexels' => 'fresh cucumbers', 'price' => 10500, 'quantity' => 350, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Qibray', 'status' => 'active'],
        ['title' => 'Qovun (Oltin vodiy)', 'category' => 'mevalar', 'pexels' => 'melon', 'price' => 9000, 'quantity' => 800, 'unit' => 'kg', 'region' => 'Xorazm', 'district' => 'Urganch', 'status' => 'active'],
        ['title' => 'Tarvuz (Chimboy)', 'category' => 'mevalar', 'pexels' => 'watermelon', 'price' => 4500, 'quantity' => 5000, 'unit' => 'kg', 'region' => 'Qoraqalpog\'iston', 'district' => 'Chimboy', 'status' => 'active'],
        ['title' => 'Asal (gul asali)', 'category' => 'konserva', 'pexels' => 'honey jar', 'price' => 60000, 'quantity' => 120, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Bo\'stonliq', 'status' => 'active'],
        ['title' => 'Yong\'oq (quritilgan)', 'category' => 'mevalar', 'pexels' => 'walnuts', 'price' => 70000, 'quantity' => 300, 'unit' => 'kg', 'region' => 'Jizzax', 'district' => 'Zomin', 'status' => 'active'],
        ['title' => 'Yangi Kartoshka (qizil)', 'category' => 'sabzavotlar', 'pexels' => 'potatoes', 'price' => 4000, 'quantity' => 0, 'unit' => 'kg', 'region' => 'Toshkent vil.', 'district' => 'Chinoz', 'status' => 'sold'],
        ['title' => 'Guruch Oq (parnik)', 'category' => 'don-va-dukkaklilar', 'pexels' => 'rice bowl', 'price' => 24500, 'quantity' => 2200, 'unit' => 'kg', 'region' => 'Namangan', 'district' => 'Uychi', 'status' => 'pending'],
    ];

    public function run(): void
    {
        $service    = app(PexelsImageService::class);
        $categories = Category::pluck('id', 'slug');
        $sellers    = User::pluck('id')->all();

        if ($categories->isEmpty() || empty($sellers)) {
            throw new \RuntimeException('ListingSeeder: avval CategorySeeder va UserSeeder ishlatilgan bo\'lishi kerak.');
        }

        $withImage = 0;

        foreach (self::LISTINGS as $index => $item) {
            $slug = Str::slug($item['title']);
            if (ListingModel::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-' . strtolower(Str::random(4));
            }

            // Har bir e'lon uchun boshqa sotuvchi
            $seller = $sellers[$index % count($sellers)];
            $images = $service->downloadImages($item['pexels'], $slug, 1);

            if ($images !== []) {
                $withImage++;
            }

            $rejectionReason = $item['status'] === 'rejected' ? 'Rasm talabga javob bermaydi' : null;

            ListingModel::create([
                'seller_id'        => $seller,
                'category_id'      => $categories[$item['category']] ?? null,
                'title'            => $item['title'],
                'slug'             => $slug,
                'description'      => "O'zimiz yetishtirgan, sifatli va yangi mahsulot. Katta hajmda oluvchilar uchun chegirma mavjud.",
                'price'            => $item['price'],
                'quantity'         => $item['quantity'],
                'unit'             => $item['unit'],
                'images'           => $images,
                'region'           => $item['region'],
                'district'         => $item['district'],
                'status'           => $item['status'],
                'rejection_reason' => $rejectionReason,
            ]);
        }

        $this->command->info('✅ E\'lonlar: ' . count(self::LISTINGS) . ' ta, ulardan ' . $withImage . ' tasida rasm bor.');
    }
}
