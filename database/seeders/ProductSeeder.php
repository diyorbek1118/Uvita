<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Services\Pexels\PexelsImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;

/**
 * Oziq-ovqat bozori mahsulotlari — real nomlar, narxlar (so'm) va
 * Pexels'dan yuklab olingan rasmlar bilan.
 *
 * Ko'pchilik mahsulotlar active; moderator ishlovini ko'rsatish uchun
 * bir nechtasi inactive (moderatsiya) va bittasi rejected (rad etilgan).
 */
class ProductSeeder extends Seeder
{
    /**
     * name, category (slug), pexels (qidiruv so'zi), price, stock,
     * description, status (ixtiyoriy), rejection_reason (ixtiyoriy)
     *
     * @var array<int, array<string, mixed>>
     */
    private const PRODUCTS = [
        // ── Sabzavotlar ────────────────────────────────────────────────
        ['name' => 'Kartoshka (1 kg)',            'category' => 'sabzavotlar',            'pexels' => 'potatoes',          'price' => 6000,   'stock' => 350, 'description' => 'Yangi hosil kartoshka. Qaynatish va qovurish uchun ideal, saralangan va toza.'],
        ['name' => 'Sabzi (1 kg)',                'category' => 'sabzavotlar',            'pexels' => 'carrots',           'price' => 5000,   'stock' => 280, 'description' => 'Shirin va sersuv sabzi. Palov, salat va sharbat tayyorlash uchun ajoyib.'],
        ['name' => 'Piyoz (1 kg)',                'category' => 'sabzavotlar',            'pexels' => 'onions',            'price' => 4500,   'stock' => 400, 'description' => 'Sifatli oq piyoz. Har qanday taomga maza beradi, uzoq saqlanadi.'],
        ['name' => 'Pomidor (1 kg)',              'category' => 'sabzavotlar',            'pexels' => 'tomatoes',          'price' => 12000,  'stock' => 120, 'description' => 'Yetilgan, xushbo\'y pomidor. Salatlar va souslar uchun eng zo\'ri.'],
        ['name' => 'Bodring (1 kg)',              'category' => 'sabzavotlar',            'pexels' => 'cucumbers',         'price' => 10000,  'stock' => 140, 'description' => 'Tiniq va yangi bodring. Tuzlash va salatlar uchun mos.'],
        ['name' => 'Bolgar qalampiri (1 kg)',     'category' => 'sabzavotlar',            'pexels' => 'bell peppers',      'price' => 15000,  'stock' => 90,  'description' => 'Sersuv bolgar qalampiri. Qovurish, salat va dumalash uchun.'],
        ['name' => 'Oq karam (1 kg)',             'category' => 'sabzavotlar',            'pexels' => 'cabbage',           'price' => 5000,   'stock' => 100, 'description' => 'Yangi oq karam. Salat va sho\'rva uchun mos, tiniq va shirali.'],
        ['name' => 'Lavlagi (1 kg)',              'category' => 'sabzavotlar',            'pexels' => 'beetroot',          'price' => 6000,   'stock' => 80,  'description' => 'Shirin lavlagi. Vinaigret, borsch va salatlar uchun.'],
        ['name' => 'Baqlajon (1 kg)',             'category' => 'sabzavotlar',            'pexels' => 'eggplant',          'price' => 13000,  'stock' => 70,  'description' => 'Yaltiroq va yangi baqlajon. Qovurish va kabob uchun ideal.'],
        ['name' => 'Qovoq (1 kg)',                'category' => 'sabzavotlar',            'pexels' => 'pumpkin',           'price' => 8000,   'stock' => 60,  'description' => 'Shirin va sersuv qovoq. Bo\'tqa, pishiriq va shirinliklar uchun.'],
        ['name' => 'Sarimsoq (1 kg)',             'category' => 'sabzavotlar',            'pexels' => 'garlic',            'price' => 28000,  'stock' => 40,  'description' => 'Xushbo\'y va achchiq sarimsoq. Taomlarga alohida maza beradi.'],
        ['name' => 'Yashil piyoz (200 g)',        'category' => 'sabzavotlar',            'pexels' => 'spring onions',     'price' => 3000,   'stock' => 50,  'description' => 'Yangi yashil piyoz. Salat, lagmon va nonushta uchun.'],
        ['name' => 'Ukrop (100 g)',               'category' => 'sabzavotlar',            'pexels' => 'dill',              'price' => 4000,   'stock' => 45,  'description' => 'Xushbo\'y yangi ukrop. Salatlar va tuzlash uchun.'],
        ['name' => 'Rediska (1 kg)',              'category' => 'sabzavotlar',            'pexels' => 'radishes',          'price' => 9000,   'stock' => 4,   'description' => 'Tiniq va yangi rediska. Bahorgi salatlar uchun ajoyib.'],
        ['name' => 'Sholg\'om (1 kg)',            'category' => 'sabzavotlar',            'pexels' => 'turnips',           'price' => 7000,   'stock' => 30,  'description' => 'Yangi sholg\'om. Salat va tuzlash uchun mos.', 'status' => 'rejected', 'rejection_reason' => 'Rasm sifati past'],
        ['name' => 'Qovoqcha (1 kg)',             'category' => 'sabzavotlar',            'pexels' => 'zucchini',          'price' => 11000,  'stock' => 55,  'description' => 'Yosh qovoqcha. Qovurish va kabob uchun yumshoq va mazali.'],

        // ── Mevalar ────────────────────────────────────────────────────
        ['name' => 'Olma (1 kg)',                 'category' => 'mevalar',                'pexels' => 'apples',            'price' => 12000,  'stock' => 150, 'description' => 'Shirin va tiniq olma. Har kuni yeyish uchun eng yaxshi meva.'],
        ['name' => 'Banan (1 kg)',                'category' => 'mevalar',                'pexels' => 'bananas',           'price' => 18000,  'stock' => 130, 'description' => 'Yetilgan va shirin banan. Bolalar uchun sevimli meva.'],
        ['name' => 'Apelsin (1 kg)',              'category' => 'mevalar',                'pexels' => 'oranges',           'price' => 16000,  'stock' => 110, 'description' => 'Sersuv va shirin apelsin. Vitamin C manbai.'],
        ['name' => 'Anor (1 kg)',                 'category' => 'mevalar',                'pexels' => 'pomegranate',       'price' => 22000,  'stock' => 4,   'description' => 'Qizil va donador anor. Sharbat va salatlar uchun.'],
        ['name' => 'Uzum (1 kg)',                 'category' => 'mevalar',                'pexels' => 'grapes',            'price' => 18000,  'stock' => 70,  'description' => 'Shirin va xushbo\'y uzum. Kushish va sharbat uchun.'],
        ['name' => 'Shaftoli (1 kg)',             'category' => 'mevalar',                'pexels' => 'peaches',           'price' => 25000,  'stock' => 50,  'description' => 'Xushbo\'y va shirali shaftoli. Yozning eng mazali mevasi.'],
        ['name' => 'Nok (1 kg)',                  'category' => 'mevalar',                'pexels' => 'pears',             'price' => 20000,  'stock' => 45,  'description' => 'Sersuv va shirin nok. Ertalabki nonushta uchun ajoyib.'],
        ['name' => 'Limon (1 kg)',                'category' => 'mevalar',                'pexels' => 'lemons',            'price' => 18000,  'stock' => 80,  'description' => 'Xushbo\'y va shirali limon. Choy va taomlar uchun.'],
        ['name' => 'Gilos (500 g)',               'category' => 'mevalar',                'pexels' => 'cherries',          'price' => 15000,  'stock' => 0,   'description' => 'Qora va shirin gilos. Murabbo va shirinliklar uchun.'],
        ['name' => 'Xurmo (1 kg)',                'category' => 'mevalar',                'pexels' => 'persimmon',         'price' => 15000,  'stock' => 40,  'description' => 'Shirin va sersuv xurmo. Kuzgi sevimli meva.'],
        ['name' => 'Tarvuz (1 kg)',               'category' => 'mevalar',                'pexels' => 'watermelon',        'price' => 5000,   'stock' => 90,  'description' => 'Sersuv va shirin tarvuz. Yozning eng yaxshi sovutuvchisi.'],
        ['name' => 'Qovun (1 kg)',                'category' => 'mevalar',                'pexels' => 'melon',             'price' => 8000,   'stock' => 70,  'description' => 'Xushbo\'y va shirin qovun. Dasturxoningizga ajoyib qo\'shimcha.'],
        ['name' => 'Mandarin (1 kg)',             'category' => 'mevalar',                'pexels' => 'mandarins',         'price' => 14000,  'stock' => 85,  'description' => 'Yengil tozalanadigan va shirin mandarin.'],
        ['name' => 'Kivi (500 g)',                'category' => 'mevalar',                'pexels' => 'kiwi',              'price' => 15000,  'stock' => 40,  'description' => 'Nordon-chuchuk kivi. Vitamin C ga juda boy.', 'status' => 'inactive'],
        ['name' => 'Qulupnay (500 g)',            'category' => 'mevalar',                'pexels' => 'strawberries',      'price' => 25000,  'stock' => 25,  'description' => 'Yangi va xushbo\'y qulupnay. Krem va murabbo uchun.', 'status' => 'inactive'],

        // ── Don va dukkaklilar ─────────────────────────────────────────
        ['name' => 'Guruch oq (1 kg)',            'category' => 'don-va-dukkaklilar',     'pexels' => 'white rice',        'price' => 24000,  'stock' => 200, 'description' => 'Sifatli oq guruch. Palov va kundalik yormalar uchun.'],
        ['name' => 'Guruch devzira (1 kg)',       'category' => 'don-va-dukkaklilar',     'pexels' => 'rice grains',       'price' => 32000,  'stock' => 150, 'description' => 'Haqiqiy devzira guruchi. An\'anaviy o\'zbek palovi uchun eng yaxshi tanlov.'],
        ['name' => 'Mosh (1 kg)',                 'category' => 'don-va-dukkaklilar',     'pexels' => 'mung beans',        'price' => 20000,  'stock' => 120, 'description' => 'Saralangan sariq mosh. Moshxo\'rida va yormalar uchun.'],
        ['name' => 'No\'xat (1 kg)',              'category' => 'don-va-dukkaklilar',     'pexels' => 'chickpeas',         'price' => 18000,  'stock' => 100, 'description' => 'Sariq no\'xat. Shavla va g\'ovurma uchun.'],
        ['name' => 'Qizil loviya (1 kg)',         'category' => 'don-va-dukkaklilar',     'pexels' => 'red beans',         'price' => 22000,  'stock' => 80,  'description' => 'Qizil loviya. Qaynatma va salatlar uchun foydali.'],
        ['name' => 'Grechka (1 kg)',              'category' => 'don-va-dukkaklilar',     'pexels' => 'buckwheat',         'price' => 28000,  'stock' => 90,  'description' => 'Toza va saralangan grechka. Foydali va to\'yimli.'],
        ['name' => 'Makaron (400 g)',             'category' => 'don-va-dukkaklilar',     'pexels' => 'dried pasta',       'price' => 7000,   'stock' => 160, 'description' => 'Sifatli makaron. Tez va mazali kechki ovqat uchun.'],
        ['name' => 'Jo\'xori yormasi (1 kg)',     'category' => 'don-va-dukkaklilar',     'pexels' => 'cornmeal',          'price' => 13000,  'stock' => 60,  'description' => 'Jo\'xori yormasi. Foydali bo\'tqa tayyorlash uchun.'],
        ['name' => 'Yasmiq (1 kg)',               'category' => 'don-va-dukkaklilar',     'pexels' => 'lentils',           'price' => 25000,  'stock' => 50,  'description' => 'Qizil yasmiq. Tez pishadi va juda foydali.'],
        ['name' => 'Bug\'doy (1 kg)',             'category' => 'don-va-dukkaklilar',     'pexels' => 'wheat grains',      'price' => 8000,   'stock' => 70,  'description' => 'Toza bug\'doy. Surnata va an\'anaviy oshlar uchun.'],
        ['name' => 'Arpa (1 kg)',                 'category' => 'don-va-dukkaklilar',     'pexels' => 'barley',            'price' => 9000,   'stock' => 55,  'description' => 'Arpa yormasi. Shavla va bo\'tqa uchun.'],
        ['name' => 'Suli yormasi (500 g)',        'category' => 'don-va-dukkaklilar',     'pexels' => 'oatmeal',           'price' => 12000,  'stock' => 65,  'description' => 'Suli yormasi. Ertalabki foydali bo\'tqa uchun.'],
        ['name' => 'Kuskus (400 g)',              'category' => 'don-va-dukkaklilar',     'pexels' => 'couscous',          'price' => 18000,  'stock' => 35,  'description' => 'Tez tayyorlanadigan kuskus. Garnir va salatlar uchun.'],
        ['name' => 'Kino (400 g)',                'category' => 'don-va-dukkaklilar',     'pexels' => 'quinoa',            'price' => 45000,  'stock' => 25,  'description' => 'Superfud kino. Foydali va to\'yimli don.', 'status' => 'inactive'],

        // ── Yog'lar ────────────────────────────────────────────────────
        ['name' => 'Kungaboqar yog\'i (1 L)',     'category' => 'yog-lar',                'pexels' => 'sunflower oil bottle', 'price' => 24000, 'stock' => 150, 'description' => 'Rafinatsiyalangan kungaboqar yog\'i. Qovurish uchun ideal.'],
        ['name' => 'Paxta yog\'i (1 L)',          'category' => 'yog-lar',                'pexels' => 'cottonseed oil',    'price' => 20000,  'stock' => 120, 'description' => 'Tozalangan paxta yog\'i. An\'anaviy va tejamkor tanlov.'],
        ['name' => 'Zaytun yog\'i (500 ml)',      'category' => 'yog-lar',                'pexels' => 'olive oil',         'price' => 65000,  'stock' => 60,  'description' => 'Extra virgin zaytun yog\'i. Salatlar va souslar uchun.'],
        ['name' => 'Sariyog\' (200 g)',           'category' => 'yog-lar',                'pexels' => 'butter',            'price' => 32000,  'stock' => 90,  'description' => 'Tabiiy sariyog\'. Nonushta va pishiriqlar uchun.'],
        ['name' => 'Margarin (250 g)',            'category' => 'yog-lar',                'pexels' => 'margarine',         'price' => 12000,  'stock' => 80,  'description' => 'Pishiriq va qovurish uchun margarin.'],
        ['name' => 'Ghee (500 g)',                'category' => 'yog-lar',                'pexels' => 'ghee',              'price' => 45000,  'stock' => 30,  'description' => 'Tiniq sariyog\' (ghee). Palov uchun ajoyib maza beradi.'],
        ['name' => 'Qovurish yog\'i (1 L)',       'category' => 'yog-lar',                'pexels' => 'frying oil',        'price' => 22000,  'stock' => 100, 'description' => 'Yuqori haroratga chidamli qovurish yog\'i.'],

        // ── Sut mahsulotlari va tuxum ──────────────────────────────────
        ['name' => 'Sut (1 L)',                   'category' => 'sut-mahsulotlari',       'pexels' => 'milk glass',        'price' => 12000,  'stock' => 100, 'description' => 'Pasterizatsiyalangan sigir suti. 3,2% yog\'lilik.'],
        ['name' => 'Qatiq (500 ml)',              'category' => 'sut-mahsulotlari',       'pexels' => 'yogurt',            'price' => 9000,   'stock' => 130, 'description' => 'Tabiiy qatiq. Turshak va soslar uchun mos.'],
        ['name' => 'Kefir (1 L)',                 'category' => 'sut-mahsulotlari',       'pexels' => 'kefir',             'price' => 12000,  'stock' => 90,  'description' => 'Yangi kefir. Hazm qilishga yordam beradi.'],
        ['name' => 'Tvorog (400 g)',              'category' => 'sut-mahsulotlari',       'pexels' => 'cottage cheese',    'price' => 18000,  'stock' => 70,  'description' => 'Yog\'li tvorog. Pishiriq va nonushta uchun.'],
        ['name' => 'Smetana (300 g)',             'category' => 'sut-mahsulotlari',       'pexels' => 'sour cream',        'price' => 14000,  'stock' => 80,  'description' => 'Qaymoqli smetana. Borsh va salatlar uchun.'],
        ['name' => 'Suzma (400 g)',               'category' => 'sut-mahsulotlari',       'pexels' => 'strained yogurt',   'price' => 15000,  'stock' => 60,  'description' => 'Quyuq va nordon suzma. Nonushta uchun ajoyib.'],
        ['name' => 'Ayron (1 L)',                 'category' => 'sut-mahsulotlari',       'pexels' => 'ayran',             'price' => 10000,  'stock' => 50,  'description' => 'Sovuq ayron. Yozgi eng yaxshi ichimlik.'],
        ['name' => 'Pishloq Suluguni (300 g)',    'category' => 'sut-mahsulotlari',       'pexels' => 'cheese',            'price' => 45000,  'stock' => 40,  'description' => 'Yumshoq suluguni pishlog\'i. Achchiq taomlar uchun.'],
        ['name' => 'Tuxum (10 dona)',             'category' => 'sut-mahsulotlari',       'pexels' => 'eggs',              'price' => 15000,  'stock' => 200, 'description' => 'Uy tovuqlari tuxumi. C1 kategoriya, yangi.'],

        // ── Shakar, un va pishiriq ─────────────────────────────────────
        ['name' => 'Shakar (1 kg)',               'category' => 'shakar-un',              'pexels' => 'sugar',             'price' => 13000,  'stock' => 180, 'description' => 'Oq shakar. Choy va pishiriqlar uchun.'],
        ['name' => 'Un (1 kg)',                   'category' => 'shakar-un',              'pexels' => 'flour',             'price' => 9000,   'stock' => 150, 'description' => 'Oliy navli un. Non va pishiriqlar uchun.'],
        ['name' => 'Irmik (500 g)',               'category' => 'shakar-un',              'pexels' => 'semolina',          'price' => 7000,   'stock' => 70,  'description' => 'Irmik yormasi. Bo\'tqa va pishiriqlar uchun.'],
        ['name' => 'Pishiriq kukuni (30 g)',      'category' => 'shakar-un',              'pexels' => 'baking powder',     'price' => 5000,   'stock' => 40,  'description' => 'Pishiriqlar yumshoq bo\'lishi uchun.'],
        ['name' => 'Soda (50 g)',                 'category' => 'shakar-un',              'pexels' => 'baking soda',       'price' => 3000,   'stock' => 45,  'description' => 'Oziq-ovqat sodasi. Pishiriqlar uchun.'],
        ['name' => 'Vanilin (2 g)',               'category' => 'shakar-un',              'pexels' => 'vanilla',           'price' => 2000,   'stock' => 50,  'description' => 'Vanilin. Shirinliklarga xushbo\'y hid beradi.'],
        ['name' => 'Xamirturush (100 g)',         'category' => 'shakar-un',              'pexels' => 'yeast',             'price' => 6000,   'stock' => 55,  'description' => 'Quruq xamirturush. Non pishirish uchun.'],
        ['name' => 'Kakao kukuni (100 g)',        'category' => 'shakar-un',              'pexels' => 'cocoa powder',      'price' => 15000,  'stock' => 35,  'description' => 'Tabiiy kakao. Pishiriq va ichimliklar uchun.', 'status' => 'inactive'],
        ['name' => 'Javdar uni (1 kg)',           'category' => 'shakar-un',              'pexels' => 'rye flour',         'price' => 12000,  'stock' => 30,  'description' => 'Javdar uni. Sog\'lom non tayyorlash uchun.'],
        ['name' => 'Quyultirilgan sut (380 g)',   'category' => 'shakar-un',              'pexels' => 'condensed milk',    'price' => 16000,  'stock' => 60,  'description' => 'Quyultirilgan sut. Shirinliklar va choy uchun.'],

        // ── Ziravorlar ─────────────────────────────────────────────────
        ['name' => 'Osh tuzi (1 kg)',             'category' => 'ziravorlar',             'pexels' => 'salt',              'price' => 4000,   'stock' => 300, 'description' => 'Mayda kristalli osh tuzi. Kundalik foydalanish uchun.'],
        ['name' => 'Qora murch (50 g)',           'category' => 'ziravorlar',             'pexels' => 'black pepper',      'price' => 12000,  'stock' => 80,  'description' => 'Maydalangan qora murch. Har bir taomga mos.'],
        ['name' => 'Zira (50 g)',                 'category' => 'ziravorlar',             'pexels' => 'cumin',             'price' => 8000,   'stock' => 90,  'description' => 'Xushbo\'y zira. Palov va kabob uchun.'],
        ['name' => 'Paprika (50 g)',              'category' => 'ziravorlar',             'pexels' => 'paprika',           'price' => 9000,   'stock' => 60,  'description' => 'Shirin paprika kukuni. Taomlarga rang va maza beradi.'],
        ['name' => 'Qizil qalampir (50 g)',       'category' => 'ziravorlar',             'pexels' => 'red chili powder',  'price' => 8000,   'stock' => 70,  'description' => 'Achchiq qizil qalampir kukuni.'],
        ['name' => 'Shashlik ziravori (30 g)',    'category' => 'ziravorlar',             'pexels' => 'kebab spice',       'price' => 7000,   'stock' => 65,  'description' => 'Kabob va shashlik uchun maxsus ziravorlar aralashmasi.'],
        ['name' => 'Dolchin (30 g)',              'category' => 'ziravorlar',             'pexels' => 'cinnamon',          'price' => 10000,  'stock' => 45,  'description' => 'Maydalangan dolchin. Pishiriqlar uchun.'],
        ['name' => 'Lavr yaprog\'i (20 g)',       'category' => 'ziravorlar',             'pexels' => 'bay leaves',        'price' => 3000,   'stock' => 85,  'description' => 'Quruq lavr yaprog\'i. Sho\'rvalar uchun.'],
        ['name' => 'Kori (50 g)',                 'category' => 'ziravorlar',             'pexels' => 'curry powder',      'price' => 12000,  'stock' => 40,  'description' => 'Kori aralashmasi. Sharq taomlari uchun.'],
        ['name' => 'Qalampir aralashmasi (50 g)', 'category' => 'ziravorlar',             'pexels' => 'peppercorns',       'price' => 10000,  'stock' => 35,  'description' => 'Turli rangdagi qalampirlar aralashmasi.'],
        ['name' => 'Xantal (200 g)',              'category' => 'ziravorlar',             'pexels' => 'mustard',           'price' => 8000,   'stock' => 50,  'description' => 'Yumshoq xantal. Go\'sht va soslar uchun.'],
        ['name' => 'Mayonez (400 g)',             'category' => 'ziravorlar',             'pexels' => 'mayonnaise',        'price' => 16000,  'stock' => 110, 'description' => 'Klassik mayonez. Salatlar uchun.'],
        ['name' => 'Kashnich (50 g)',             'category' => 'ziravorlar',             'pexels' => 'coriander',         'price' => 9000,   'stock' => 30,  'description' => 'Maydalangan kashnich. Taomlarga alohida maza beradi.'],

        // ── Ichimliklar ────────────────────────────────────────────────
        ['name' => 'Qora choy (100 g)',           'category' => 'ichimliklar',            'pexels' => 'black tea',         'price' => 18000,  'stock' => 130, 'description' => 'Seylon qora choyi. Xushbo\'y va tiniq damlama.'],
        ['name' => 'Yashil choy (100 g)',         'category' => 'ichimliklar',            'pexels' => 'green tea',         'price' => 25000,  'stock' => 90,  'description' => 'Yuqori sifatli yashil choy. O\'zbek dasturxoni uchun.'],
        ['name' => 'Ko\'k choy (50 g)',           'category' => 'ichimliklar',            'pexels' => 'green tea leaves',  'price' => 12000,  'stock' => 60,  'description' => 'Birinchi hosil ko\'k choy barglari. Xushbo\'y va tetiklashtiradi.'],
        ['name' => 'Qahva donalari (250 g)',      'category' => 'ichimliklar',            'pexels' => 'coffee beans',      'price' => 85000,  'stock' => 40,  'description' => 'Arabika qahva donalari. O\'rta qovurilgan.'],
        ['name' => 'Eriydigan qahva (100 g)',     'category' => 'ichimliklar',            'pexels' => 'instant coffee',    'price' => 45000,  'stock' => 50,  'description' => 'Tez tayyorlanadigan eriydigan qahva.'],
        ['name' => 'Mineral suv (1.5 L)',         'category' => 'ichimliklar',            'pexels' => 'mineral water bottle', 'price' => 5000, 'stock' => 250, 'description' => 'Gazsiz mineral ichimlik suvi.'],
        ['name' => 'Gazlangan suv (1 L)',         'category' => 'ichimliklar',            'pexels' => 'sparkling water',   'price' => 6000,   'stock' => 180, 'description' => 'Yengil gazlangan ichimlik suvi.'],
        ['name' => 'Olma sharbati (1 L)',         'category' => 'ichimliklar',            'pexels' => 'apple juice',       'price' => 18000,  'stock' => 80,  'description' => '100% tabiiy olma sharbati. Qo\'shimchasiz.'],
        ['name' => 'Anor sharbati (1 L)',         'category' => 'ichimliklar',            'pexels' => 'pomegranate juice', 'price' => 35000,  'stock' => 50,  'description' => 'Tabiiy anor sharbati. Vitaminlarga boy.'],
        ['name' => 'Shaftoli sharbati (1 L)',     'category' => 'ichimliklar',            'pexels' => 'peach juice',       'price' => 20000,  'stock' => 45,  'description' => 'Yangi shaftolidan tayyorlangan sharbat.'],
        ['name' => 'Limonad (1.5 L)',             'category' => 'ichimliklar',            'pexels' => 'lemonade',          'price' => 12000,  'stock' => 100, 'description' => 'Sitrus limonad. Sovuq holda iching.'],
        ['name' => 'Quruq mevalar kompoti (500 g)', 'category' => 'ichimliklar',          'pexels' => 'dried fruit compote', 'price' => 15000, 'stock' => 55,  'description' => 'Quruq olma, o\'rik va mayiz aralashmasi.'],

        // ── Konserva va tayyor mahsulotlar ─────────────────────────────
        ['name' => 'Tomat pastasi (500 g)',       'category' => 'konserva',               'pexels' => 'tomato paste',      'price' => 14000,  'stock' => 90,  'description' => 'Qalin tomat pastasi. Taomlarga boy maza beradi.'],
        ['name' => 'Tuzlangan bodring (700 g)',   'category' => 'konserva',               'pexels' => 'pickles',           'price' => 18000,  'stock' => 70,  'description' => 'An\'anaviy usulda tuzlangan bodring.'],
        ['name' => 'Pomidor konservasi (400 g)',  'category' => 'konserva',               'pexels' => 'canned tomatoes',   'price' => 12000,  'stock' => 60,  'description' => 'O\'z sharbatida tilimlangan pomidor konservasi.'],
        ['name' => 'Olma murabbosi (400 g)',      'category' => 'konserva',               'pexels' => 'apple jam',         'price' => 25000,  'stock' => 50,  'description' => 'Uy uslubida tayyorlangan olma murabbosi.'],
        ['name' => 'Baliq konservasi (240 g)',    'category' => 'konserva',               'pexels' => 'canned fish',       'price' => 22000,  'stock' => 80,  'description' => 'Yog\'da baliq konservasi. Aperitiv uchun.'],
        ['name' => 'Makkajo\'xori konservasi (340 g)', 'category' => 'konserva',          'pexels' => 'canned corn',       'price' => 13000,  'stock' => 75,  'description' => 'Shirin makkajo\'xori. Salatlar uchun.'],
        ['name' => 'Yashil no\'xat konservasi (400 g)', 'category' => 'konserva',         'pexels' => 'canned peas',       'price' => 14000,  'stock' => 65,  'description' => 'Yashil no\'xat. Salat va garnir uchun.'],
        ['name' => 'Asal (500 g)',                'category' => 'konserva',               'pexels' => 'honey',             'price' => 55000,  'stock' => 40,  'description' => 'Tabiiy gul asali. Choy uchun eng yaxshi.'],
        ['name' => 'Qo\'ziqorin konservasi (300 g)', 'category' => 'konserva',            'pexels' => 'canned mushrooms',  'price' => 18000,  'stock' => 45,  'description' => 'Shampinyon qo\'ziqorini konservasi.'],
        ['name' => 'Zaytun (200 g)',              'category' => 'konserva',               'pexels' => 'olives',            'price' => 15000,  'stock' => 3,   'description' => 'Ko\'magi bilan zaytun. Aperitiv uchun.'],

        // ── Go'sht, parranda va baliq ──────────────────────────────────
        ['name' => 'Mol go\'shti (1 kg)',         'category' => 'gosht-parranda',         'pexels' => 'beef',              'price' => 92000,  'stock' => 60,  'description' => 'Yosh mol go\'shti. Shashlik va kabob uchun.'],
        ['name' => 'Qo\'y go\'shti (1 kg)',       'category' => 'gosht-parranda',         'pexels' => 'lamb',              'price' => 110000, 'stock' => 40,  'description' => 'Yumshoq qo\'y go\'shti. Palov uchun eng yaxshi.'],
        ['name' => 'Tovuq filesi (1 kg)',         'category' => 'gosht-parranda',         'pexels' => 'chicken breast',    'price' => 38000,  'stock' => 80,  'description' => 'Toza tovuq filesi. Kam yog\'li go\'sht.'],
        ['name' => 'Tovuq oyoqlari (1 kg)',       'category' => 'gosht-parranda',         'pexels' => 'chicken legs',      'price' => 28000,  'stock' => 90,  'description' => 'Tovuq oyoqlari. Qovurish va qaynatish uchun.'],
        ['name' => 'Tovuq qanotlari (1 kg)',      'category' => 'gosht-parranda',         'pexels' => 'chicken wings',     'price' => 25000,  'stock' => 70,  'description' => 'Tovuq qanotlari. Gril uchun ajoyib.'],
        ['name' => 'Kurka go\'shti (1 kg)',       'category' => 'gosht-parranda',         'pexels' => 'turkey',            'price' => 55000,  'stock' => 30,  'description' => 'Yumshoq kurka go\'shti. Parhez uchun mos.'],
        ['name' => 'Baliq (1 kg)',                'category' => 'gosht-parranda',         'pexels' => 'fresh fish',        'price' => 45000,  'stock' => 35,  'description' => 'Sovutilgan yangi baliq. Qovurish uchun.'],
        ['name' => 'Kolbasa (500 g)',             'category' => 'gosht-parranda',         'pexels' => 'sausage',           'price' => 35000,  'stock' => 60,  'description' => 'Qaynatilgan kolbasa. Sendvichlar uchun.'],
        ['name' => 'Sosiska (400 g)',             'category' => 'gosht-parranda',         'pexels' => 'sausages',          'price' => 25000,  'stock' => 70,  'description' => 'Mazali sosiskalar. Tez tayyorlanadi.'],
        ['name' => 'Qiyma (500 g)',               'category' => 'gosht-parranda',         'pexels' => 'ground beef',       'price' => 45000,  'stock' => 50,  'description' => 'Yangi mol qiymasi. Kotlet va manti uchun.'],
        ['name' => 'Mol jigari (1 kg)',           'category' => 'gosht-parranda',         'pexels' => 'beef liver',        'price' => 35000,  'stock' => 30,  'description' => 'Yangi mol jigari. Foydali va mazali.'],
        ['name' => 'Dudlangan kolbasa (400 g)',   'category' => 'gosht-parranda',         'pexels' => 'smoked sausage',    'price' => 30000,  'stock' => 45,  'description' => 'Dudlangan kolbasa. Aperitiv uchun.'],
    ];

    public function run(): void
    {
        $service    = app(PexelsImageService::class);
        $categories = Category::pluck('id', 'slug');
        $managerId  = Staff::where('role', 'manager')->value('id');

        // Kategoriya slug'larini oldindan tekshiramiz — xatolik bo'lsa darhol chiqamiz
        foreach (self::PRODUCTS as $item) {
            if (! isset($categories[$item['category']])) {
                throw new \RuntimeException(
                    "ProductSeeder: kategoriya topilmadi — slug: '{$item['category']}' (mahsulot: {$item['name']})"
                );
            }
        }

        $withImage = 0;

        foreach (self::PRODUCTS as $item) {
            $slug = Str::slug($item['name']);

            if (Product::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-' . strtolower(Str::random(4));
            }

            $images = $service->downloadImages($item['pexels'], $slug, 2);

            if ($images !== []) {
                $withImage++;
            }

            Product::create([
                'name'             => $item['name'],
                'slug'             => $slug,
                'description'      => $item['description'],
                'price'            => $item['price'],
                'stock'            => $item['stock'],
                'status'           => $item['status'] ?? 'active',
                'images'           => $images,
                'category_id'      => $categories[$item['category']] ?? null,
                'manager_id'       => $managerId,
                'rejection_reason' => $item['rejection_reason'] ?? null,
            ]);
        }

        $this->command->info('✅ Mahsulotlar: ' . count(self::PRODUCTS) . ' ta, ulardan ' . $withImage . ' tasida rasm bor.');
    }
}
