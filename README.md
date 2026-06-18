# Uvita — Online Marketplace API

Laravel 12 asosida qurilgan modular monolith REST API.

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
| POST | `/auth/send-otp` | OTP kod yuborish |
| POST | `/auth/verify-otp` | OTP kodni tasdiqlash |
| POST | `/auth/logout` | Chiqish |

### send-otp

```json
POST /api/auth/send-otp
Content-Type: application/json

{
    "phone": "+998901234567",
    "type": "login"
}
```

### verify-otp

```json
POST /api/auth/verify-otp
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
├── Product/      — mahsulotlar
├── Cart/         — savat
└── Order/        — buyurtmalar
```

## Litsenziya

MIT
