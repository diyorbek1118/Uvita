# Uvita backend

Uvita - omborsiz ulgurji marketplace va logistika platformasining Laravel API'si.
Mahsulot seller manzilidan courier orqali to'g'ridan-to'g'ri xaridorga yetkaziladi.

## Asosiy hujjatlar

- [Yagona biznes lifecycle](LIFECYCLE.md)
- [Agent va kod sifati qoidalari](AGENTS.md)
- [Loyiha strukturasi](STRUCTURE.md)
- [Texnik hujjatlar indeksi](docs/README.md)
- [Order lifecycle tafsiloti](docs/ORDER_LIFECYCLE.md)
- [Courier trip tafsiloti](docs/COURIER_TRIP_FLOW.md)

`LIFECYCLE.md` boshqa eski hujjat yoki kodga zid bo'lsa, lifecycle ustuvor.

## Stack

- Laravel 12
- PHP `composer.json` talabiga mos, production target PHP 8.4
- MySQL 8
- Redis
- Sanctum
- PHPUnit

## Lokal ishga tushirish

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
```

Servis nomlari amaldagi `docker-compose.yml`dan olinadi. Global `/redis` kabi umumiy
container nomidan foydalanilmaydi; loyiha prefiksi yoki Compose project nomi ishlatiladi.

## API guruhlari

```text
/api/*             public va customer
/api/seller/*      seller panel
/api/dashboard/*   xodimlar dashboardi
/api/admin/*       admin compatibility endpointlari
/api/super/*       super admin compatibility endpointlari
/api/courier/*     courier ilovasi
```

Route mavjudligi va aniq endpointlar modul ichidagi
`Presentation/routes/api.php` fayllaridan tekshiriladi.

## Asosiy modullar

- Auth va User
- Seller
- Category va Product
- Cart va Order
- Courier
- Admin/Dashboard
- Payment - mavjud legacy/integration boundary; birinchi relizda real online payment emas
- Review/Rating va boshqa yordamchi modullar

Birinchi reliz naqd to'lov bilan ishlaydi. Soliq va fiskal chek integratsiyasi hali
ochiq masala; productionga yuborish alohida tasdiqsiz yoqilmaydi.

## Sifat tekshiruvi

```bash
php artisan test
vendor/bin/pint --test
```

Queryga ta'sir qilgan har o'zgarishda N+1, pagination, query count, index va muhim
query plan `AGENTS.md` qoidalari bo'yicha tekshiriladi.

## Xavfsizlik

- `.env`, token, OTP/PIN va provider secretlari commit qilinmaydi.
- Har private endpoint permission va ownershipni backendda tekshiradi.
- Muhim stock, status va moliyaviy amallar transaction/idempotency bilan bajariladi.
- Production loglari maxfiy ma'lumot saqlamaydi.

## Litsenziya

MIT
