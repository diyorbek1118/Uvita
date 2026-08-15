# Uvita — B2B va B2B2C Farm Marketplace

Fermer/dehqon mahsulotlarini sellerlar orqali biznes xaridorlar va customerlarga
yetkazish uchun Laravel 12 asosida qurilgan modular monolith REST API.

## Dokumentatsiya

- [Buyurtma va yetkazish jarayoni](docs/ORDER_LIFECYCLE.md)

## Texnologiyalar

- **Laravel 12** — PHP framework
- **PHP 8.4** — dasturlash tili
- **MySQL 8** — asosiy ma'lumotlar bazasi
- **Redis** — OTP saqlash, cache, session
- **Docker** — konteynerizatsiya
- **Laravel Sanctum** — token autentifikatsiya

## O'rnatish

### Talablar

- Docker
- Docker Compose
- Git

### Qadamlar

```bash
# 1. Reponi klonlash
git clone https://github.com/diyorbek1118/Uvita.git
cd Uvita

# 2. .env faylini sozlash
cp .env.example .env

# 3. Docker konteynerlarini ishga tushirish
docker-compose up -d --build

# 4. App kalitini yaratish
docker exec app php artisan key:generate

# 5. Migratsiyalarni ishga tushirish
docker exec app php artisan migrate
```

## API

Base URL: `http://localhost:8000/api`

### Auth

| Method | Endpoint | Tavsif |
|--------|----------|--------|
| POST | `/auth/otp/send` | OTP kod yuborish |
| POST | `/auth/otp/verify` | OTP kodni tasdiqlash |
| POST | `/auth/logout` | Chiqish |

### send-otp

```json
POST /api/auth/otp/send
Content-Type: application/json

{
    "phone": "+998901234567",
    "type": "login"
}
```

### verify-otp

```json
POST /api/auth/otp/verify
Content-Type: application/json

{
    "phone": "+998901234567",
    "code": "123456",
    "type": "login"
}
```

### logout

```json
POST /api/auth/logout
Authorization: Bearer {token}
```

## Loyiha strukturasi

```
Modules/
├── Auth/         — autentifikatsiya
├── User/         — foydalanuvchi
├── Product/      — mahsulot, media, fee va versiyalangan moderatsiya
├── Cart/         — savat
├── Order/        — buyurtmalar
├── Seller/       — seller profil/KYB va tasdiqlash
├── Payment/      — Payme, Click, Uzum
├── Review/       — moderatsiyali sharhlar
├── Courier/      — assignment, PIN, GPS, payout
└── Admin/        — moderatsiya va analitika
```

Seller dashboard backenddan mustaqil `../uvita_frontend_seller/` loyihasida joylashgan. Seller mahsulot
yaratganda kamida 4 ta rasm va bitta video yuklaydi, server komissiyalarni hisoblaydi
va mahsulot/tahrir admin tasdig'idan keyingina marketga chiqadi.

Frontendlar alohida deploy qilinadi:

```text
uvita_frontend/             Customer va B2B xaridor marketi
uvita_frontend_dashboard/   Manager, admin va super-admin paneli
uvita_frontend_seller/      Seller kabineti
```

## Litsenziya

MIT
