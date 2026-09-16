# Uvita backend strukturasi

Bu hujjat barqaror arxitektura va modul chegaralarini ko'rsatadi. Har bir faylning
qo'lda yozilgan ro'yxati emas. Joriy fayllarni `rg --files` bilan ko'rish kerak.

## Ekotizim

```text
uvita/
├── uvita_backend/             Laravel API va biznes qoidalari
├── uvita_frontend/            React customer/B2B market
├── uvita_frontend_dashboard/  React operatsion dashboard
├── uvita_frontend_seller/     Next/React seller kabineti
└── Uvita-kuryer/              Flutter courier ilovasi
```

Frontendlar mustaqil build/deploy qilinadi. Narx, permission, status, stock va moliya
qoidalarining yakuniy tekshiruvi backendda bajariladi.

## Backend ildizi

```text
uvita_backend/
├── app/                 Laravel bootstrapga yaqin umumiy qatlam
├── bootstrap/           framework bootstrap va modul route yuklash
├── config/              framework va provider konfiguratsiyasi
├── Modules/             biznes modullari
├── docs/                focused texnik hujjatlar
├── tests/               unit, feature va integration testlar
├── AGENTS.md            agent/kod sifati qoidalari
├── LIFECYCLE.md         yagona biznes lifecycle
├── STRUCTURE.md         ushbu arxitektura xaritasi
└── docker-compose*.yml  local va production containerlari
```

## Umumiy `app/` qatlami

`app/` faqat bir nechta modulga umumiy bo'lgan framework integratsiyasi uchun:

```text
app/
├── Exceptions/          global exception mapping
├── Http/Middleware/     guard, permission va JSON middleware
├── Jobs/                SMS, push va boshqa async ishlar
├── Providers/           repository binding va modul bootstrap
└── Shared/
    ├── Exceptions/
    ├── Responses/
    └── Services/        settings, fee, upload va provider contractlari
```

Yangi biznes controller/model/request global `app/Http` yoki `app/Models`ga qo'yilmaydi.

## Modul shabloni

```text
Modules/{Module}/
├── Domain/
│   ├── Entities/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Repositories/    faqat interface
│   ├── Services/
│   └── ValueObjects/
├── Application/
│   ├── Commands/
│   ├── DTOs/
│   ├── Handlers/
│   └── Queries/
├── Infrastructure/
│   ├── External/
│   └── Persistence/
│       ├── Migrations/
│       ├── Models/
│       └── Repositories/
└── Presentation/
    ├── Controllers/
    ├── Requests/
    ├── Resources/
    └── routes/api.php
```

Har modulda barcha papka bo'lishi shart emas. Keraksiz bo'sh qatlam yaratilmaydi.

## Joriy modullar

| Modul | Mas'uliyat |
|---|---|
| Auth | OTP, token va login xavfsizligi |
| User | Customer profil va manzilga tegishli asoslar |
| Seller | Seller/do'kon profili, verifikatsiya va ownership |
| Category | Kategoriya va katalog ierarxiyasi |
| Product | Mahsulot, media, stock va revision/moderatsiya |
| Cart | Persistent savatcha va item validatsiyasi |
| Order | Checkout, order, status, snapshot va seller fulfillment |
| Courier | Courier profil, transport, offer, trip, pickup, PIN va cash oqimi |
| Admin | Dashboard operatsiyalari, setting, permission va auditga yaqin use-caselar |
| Payment | Provider boundary va legacy payment kodi; cash-first lifecycle'ni boshqarmaydi |
| Review/Rating | Sharh va reyting imkoniyatlari |
| Listing/Deal/Chat | Mavjud yordamchi/kelajak modullar; yangi lifecycle'ga qo'shishdan oldin audit qilinadi |

Mavjud modul nomi biznes qoidasining to'g'riligini isbotlamaydi. Yangi ish doimo
`LIFECYCLE.md` bilan solishtiriladi.

## Route guruhlari

```text
/api/*
/api/seller/*
/api/dashboard/*
/api/admin/*
/api/super/*
/api/courier/*
```

Route modulning `Presentation/routes/api.php` faylida turadi. Yangi global
`routes/api.php` biznes endpointi yaratilmaydi.

## Ma'lumot bazasi

- Har modul migratsiyasi o'sha modul `Infrastructure/Persistence/Migrations` ichida.
- Foreign key va ko'p ishlatiladigan filter/join/sort indexlari query shakliga mos yoziladi.
- Pul va miqdor aniq integer/decimalda saqlanadi; float ishlatilmaydi.
- Stock va moliya kabi raqobatli yozuvlar transaction va lock bilan.
- Audit/ledger yozuvlari append-only tamoyilida.

## Test strukturasi

```text
tests/
├── Unit/          sof domain qoida va hisoblar
├── Feature/       endpoint, validation, permission va response
└── Integration/   DB, lock, idempotency, queue va provider chegarasi
```

Amaldagi repo ayrim testlarni boshqa nomda saqlashi mumkin. Yangi test mavjud
konvensiyaga qo'shiladi, parallel takroriy test papkasi ochilmaydi.

## Hujjatlar

```text
LIFECYCLE.md                 biznesning yagona asosiy manbasi
AGENTS.md                    texnik va quality gate
STRUCTURE.md                 barqaror arxitektura
docs/README.md               texnik hujjatlar indeksi
docs/ORDER_LIFECYCLE.md      orderning texnik/business mappingi
docs/COURIER_TRIP_FLOW.md    courier trip tafsiloti
```

Takroriy "to'liq hujjat" saqlanmaydi, chunki u tezda lifecycle bilan ziddiyatga kiradi.

## Yangilash qoidasi

`STRUCTURE.md` faqat quyidagilarda yangilanadi:

- yangi bounded context/modul qo'shilsa yoki olib tashlansa;
- route guruhining ownershipi o'zgarsa;
- top-level papka yoki qatlam qoidasi o'zgarsa;
- yangi umumiy infrastructure boundary paydo bo'lsa.

Oddiy class, request yoki test qo'shilganda bu faylga har bir filename yozilmaydi.
