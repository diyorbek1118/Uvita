<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class ProductSeeder extends Seeder
{
    private const IMAGE = '?auto=format&fit=crop&w=1200&q=86';

    /** @var array<int, array<string, mixed>> */
    private const PRODUCTS = [
        ['name' => 'Parkent qizil olmasi', 'category' => 'mevalar', 'price' => 14500, 'stock' => 240, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Toshkent viloyati', 'farmer' => 'Azizbek Rahimov', 'image' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6', 'description' => 'Parkent bog‘larida yetishtirilgan qizil olma. Mevalar qo‘lda saralangan, qattiq va sersuv. HoReCa, do‘kon va oilaviy xarid uchun 5 kg dan yetkaziladi.'],
        ['name' => 'Yangi hosil kartoshka', 'category' => 'sabzavotlar', 'price' => 6800, 'stock' => 1200, 'unit' => 'kg', 'minimum' => 10, 'origin' => 'Samarqand', 'farmer' => 'Zarafshon Agro', 'image' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655', 'description' => 'Samarqand dalalaridan yangi kovlangan, o‘rta kalibrli kartoshka. Qovurish, qaynatish va umumiy oshxona uchun mos. Quruq, saralangan va to‘r qoplarda yuboriladi.'],
        ['name' => 'Mirzacho‘l sabzisi', 'category' => 'sabzavotlar', 'price' => 5200, 'stock' => 680, 'unit' => 'kg', 'minimum' => 10, 'origin' => 'Jizzax', 'farmer' => 'Mirzacho‘l Hosili', 'image' => 'https://images.unsplash.com/photo-1447175008436-054170c2e979', 'description' => 'Palovbop, to‘q sariq rangli va shirin sabzi. Bir xil o‘lchamda saralangan, yuvilmagan holda uzoq saqlashga mos.'],
        ['name' => 'Issiqxona pomidori', 'category' => 'sabzavotlar', 'price' => 17800, 'stock' => 160, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Toshkent viloyati', 'farmer' => 'Green Valley Farm', 'image' => 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337', 'description' => 'Qibray issiqxonalaridan bir xil kalibrdagi qizil pomidor. Salat va chakana savdo uchun mos, ezilmasligi uchun plastik yashiklarda yetkaziladi.'],
        ['name' => 'Oq no‘xat — saralangan', 'category' => 'don-va-dukkaklilar', 'price' => 19500, 'stock' => 420, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Qashqadaryo', 'farmer' => 'Nasaf Don', 'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d9/Ordinary_chickpeas_in_a_ceramic_bowl.jpg/1280px-Ordinary_chickpeas_in_a_ceramic_bowl.jpg', 'description' => 'Yirik donli, tozalangan oq no‘xat. Osh, sho‘rva va qayta qadoqlash uchun mos. Namlikdan himoyalangan qoplarda saqlanadi.'],
        ['name' => 'Oziq-ovqat bug‘doyi', 'category' => 'don-va-dukkaklilar', 'price' => 4600, 'stock' => 3500, 'unit' => 'kg', 'minimum' => 50, 'origin' => 'Sirdaryo', 'farmer' => 'Sirdaryo Grain', 'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b4/Wheat_close-up.JPG/1280px-Wheat_close-up.JPG', 'description' => '2026-yil hosili, tozalangan oziq-ovqat bug‘doyi. Un ishlab chiqarish va ulgurji xarid uchun. Laboratoriya namlik ko‘rsatkichi me’yorda.'],
        ['name' => 'Parkent qora uzumi', 'category' => 'mevalar', 'price' => 22000, 'stock' => 95, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Toshkent viloyati', 'farmer' => 'Parkent Uzumzori', 'image' => 'https://images.unsplash.com/photo-1537640538966-79f369143f8f', 'description' => 'Shirin, yirik donali qora uzum. Ertalab uzilib, ventilyatsiyali yashiklarda jo‘natiladi. Dasturxon va chakana savdo uchun premium saralash.'],
        ['name' => 'Mirzacho‘l tarvuzi', 'category' => 'mevalar', 'price' => 3900, 'stock' => 900, 'unit' => 'kg', 'minimum' => 20, 'origin' => 'Jizzax', 'farmer' => 'Mirzacho‘l Polizi', 'image' => 'https://images.unsplash.com/photo-1563114773-84221bd62daa', 'description' => 'Tabiiy sharoitda yetilgan, qizil va sersuv tarvuz. Har biri taxminan 7–11 kg. Ulgurji buyurtmalar daladan to‘g‘ridan-to‘g‘ri jo‘natiladi.'],
        ['name' => 'Obinovvot qovuni', 'category' => 'mevalar', 'price' => 8500, 'stock' => 310, 'unit' => 'kg', 'minimum' => 10, 'origin' => 'Sirdaryo', 'farmer' => 'Oqoltin Poliz', 'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Cucumis_melo_var._reticulatus_%28photo_by_Scott_Bauer%29.jpg/1280px-Cucumis_melo_var._reticulatus_%28photo_by_Scott_Bauer%29.jpg', 'description' => 'Xushbo‘y va shirin obinovvot qovuni. Pishganlik darajasi tekshirilib, tashishga chidamli mahsulotlar alohida saralanadi.'],
        ['name' => 'Birinchi nav oq piyoz', 'category' => 'sabzavotlar', 'price' => 4300, 'stock' => 1800, 'unit' => 'kg', 'minimum' => 20, 'origin' => 'Samarqand', 'farmer' => 'Bulung‘ur Agro', 'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/70/Onions_3.jpg/1280px-Onions_3.jpg', 'description' => 'Quruq po‘stli, birinchi nav oq piyoz. Omborda saqlash va ulgurji savdo uchun mos. 20 kg to‘r qoplarda yetkaziladi.'],
        ['name' => 'Devzira guruchi', 'category' => 'don-va-dukkaklilar', 'price' => 32500, 'stock' => 260, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Farg‘ona', 'farmer' => 'Rishton Baraka', 'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cf/Basmati_Rice.jpg/1280px-Basmati_Rice.jpg', 'description' => 'Farg‘ona vodiysida yetishtirilgan asl devzira. Doni og‘ir, suvni yaxshi shimadi va palovda uvalanib ketmaydi.'],
        ['name' => 'Mavsumiy gilos', 'category' => 'mevalar', 'price' => 28000, 'stock' => 0, 'unit' => 'kg', 'minimum' => 3, 'origin' => 'Samarqand', 'farmer' => 'Urgut Bog‘lari', 'image' => 'https://images.unsplash.com/photo-1528821128474-27f963b062bf', 'description' => 'Shirin, yirik gilos. Joriy partiya tugagan; yangi terim kelganda qoldiq yangilanadi.'],
        ['name' => 'Eksportbop qizil qalampir', 'category' => 'sabzavotlar', 'price' => 24000, 'stock' => 80, 'unit' => 'kg', 'minimum' => 5, 'origin' => 'Surxondaryo', 'farmer' => 'Termiz Fresh', 'image' => 'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83', 'description' => 'Eksport kalibridagi qizil bulg‘or qalampiri. Mahsulot hujjatlari moderatsiya tekshiruvida.', 'status' => 'inactive'],
        ['name' => 'Uy sharoitidagi quritilgan meva', 'category' => 'mevalar', 'price' => 65000, 'stock' => 25, 'unit' => 'kg', 'minimum' => 2, 'origin' => 'Samarqand', 'farmer' => 'Bog‘iston', 'image' => 'https://images.unsplash.com/photo-1595411425732-e69c1abe2763', 'description' => 'Quritilgan mevalar aralashmasi.', 'status' => 'rejected', 'rejection_reason' => 'Mahsulot tarkibi va qadoq rasmi yetarli ko‘rsatilmagan.'],
    ];

    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $seller = Staff::where('email', 'seller@uvita.uz')->firstOrFail();
        $shop = SellerProfileModel::where('seller_id', $seller->id)->firstOrFail();
        $managerId = Staff::where('role', 'manager')->value('id');
        $photoQueries = [
            'Parkent qizil olmasi' => 'apples,fruit',
            'Yangi hosil kartoshka' => 'potatoes,vegetable',
            'Mirzacho‘l sabzisi' => 'carrots,vegetable',
            'Issiqxona pomidori' => 'tomatoes,fruit',
            'Oq no‘xat — saralangan' => 'chickpeas,food',
            'Oziq-ovqat bug‘doyi' => 'wheat,grain',
            'Parkent qora uzumi' => 'grapes,fruit',
            'Mirzacho‘l tarvuzi' => 'watermelon,fruit',
            'Obinovvot qovuni' => 'melon,fruit',
            'Birinchi nav oq piyoz' => 'onions,vegetable',
            'Devzira guruchi' => 'rice,grain',
            'Mavsumiy gilos' => 'cherries,fruit',
            'Eksportbop qizil qalampir' => 'peppers,vegetable',
            'Uy sharoitidagi quritilgan meva' => 'driedfruit,food',
        ];

        foreach (self::PRODUCTS as $index => $item) {
            $primaryImage = str_contains($item['image'], 'images.unsplash.com')
                ? $item['image'].self::IMAGE
                : $item['image'];
            $query = $photoQueries[$item['name']];
            $seed = 1000 + ($index * 10);

            Product::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'description' => $item['description'],
                'price' => $item['price'],
                'stock' => $item['stock'],
                'status' => $item['status'] ?? 'active',
                'images' => [
                    $primaryImage,
                    "https://loremflickr.com/1200/900/{$query}/all?lock=".($seed + 1),
                    "https://loremflickr.com/1200/900/{$query}/all?lock=".($seed + 2),
                    "https://loremflickr.com/1200/900/{$query}/all?lock=".($seed + 3),
                ],
                'category_id' => $categories[$item['category']],
                'manager_id' => $managerId,
                'seller_id' => $seller->id,
                'seller_profile_id' => $shop->id,
                'approved_version' => ($item['status'] ?? 'active') === 'active' ? 1 : 0,
                'origin_region' => $item['origin'],
                'farmer_name' => $item['farmer'],
                'unit' => $item['unit'],
                'minimum_order_quantity' => $item['minimum'],
                'primary_image_index' => 0,
                'rejection_reason' => $item['rejection_reason'] ?? null,
            ]);
        }

        $this->command?->info('✓ 14 ta mahsulot, har birida 4 tadan real foto yaratildi.');
    }

}
