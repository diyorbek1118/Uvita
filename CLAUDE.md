# Uvita Backend — Claude Code Qo'llanmasi

## Majburiy: Har Sessiya Boshida O'qi
- `LIFECYCLE.md`  — biznes qoidalar va barcha oqimlar
- `STRUCTURE.md`  — loyiha papka va fayl arxitekturasi

---

## Texnik Stack

```
PHP:      8.4
Laravel:  12.x
DB:       PostgreSQL
Cache:    Redis (queue + session uchun)
```

### PHP 8.4 — Ishlatish Kerak Bo'lgan Xususiyatlar

```php
// 1. Property Hooks — VO larda
class Money {
    public int $cents {
        get => $this->amount * 100;
    }
}

// 2. Asymmetric Visibility — Entity larda
class Order {
    public private(set) OrderStatus $status;
}

// 3. Readonly — DTO va VO larda majburiy
class CreateOrderDTO {
    public function __construct(
        public readonly int   $userId,
        public readonly array $items,
    ) {}
}

// 4. #[\Deprecated] — eski metodlarni belgilash
#[\Deprecated('Use createOrder() instead')]
public function makeOrder(): void {}
```

### Laravel 12 — Muhim Farqlar

```
Carbon 3.x   — Carbon 2 olib tashlangan, hamma joyda Carbon 3
UUIDv7        — HasUuids endi UUIDv7 generatsiya qiladi
SVG upload    — image() rule SVG qabul qilmaydi
               Kerak bo'lsa: image:allow_svg yoki File::image(allowSvg: true)
Strict types  — declare(strict_types=1) hamma PHP faylda majburiy
```

---

## Packages

### Production

```
laravel/sanctum               — Customer OTP auth (API token)
spatie/laravel-query-builder  — Product filter, sort, qidiruv
```

### Custom (package yo'q, o'zimiz yozamiz)

```
Role/Permission  — Custom guards (manager, courier, admin, super_admin)
Command Bus      — Handler larni to'g'ridan DI orqali chaqiramiz (package yo'q)
SMS              — app/Shared/Services/SMS/SmsService.php
Telegram         — app/Shared/Services/Telegram/TelegramService.php
Payment          — Har provider uchun alohida Adapter (Payme, Click, Uzum)
```

### Development Only

```
barryvdh/laravel-ide-helper   — IDE type hints
```

---

## Arxitektura: Pragmatik DDD

### Har Bir Modul 4 Qatlamdan Iborat

```
Modules/Order/
├── Domain/           — Sof biznes qoidalar (tashqi dunyodan mustaqil)
├── Application/      — Use-case lar
├── Infrastructure/   — DB, tashqi servislar
└── Presentation/     — HTTP qatlami
```

### Qatlamlar Tarkibi

```
Domain/
├── Entities/           — Sof PHP class (Eloquent yo'q)
├── ValueObjects/       — Money, DeliveryAddress, NotFoundReason
├── Events/             — OrderPaid, OrderDelivered (Domain events)
├── Exceptions/         — InsufficientStockException, InvalidOtpException
├── Repositories/       — Faqat Interface (implementation bu yerda emas)
└── Services/           — Sof biznes qoidalar (DomainService)

Application/
├── Commands/           — CreateOrderCommand, ConfirmOrderCommand
├── Handlers/           — CreateOrderHandler (handle() metodi)
├── Queries/            — GetOrderByIdQuery, GetOrderListQuery
└── DTOs/               — CreateOrderDTO, UpdateProfileDTO

Infrastructure/
├── Persistence/
│   ├── Models/         — Eloquent Model (faqat bu yerda)
│   ├── Repositories/   — EloquentOrderRepository (Interface ni implement qiladi)
│   └── Migrations/
└── External/           — SmsAdapter, TelegramAdapter, PaymentAdapter

Presentation/
├── Controllers/        — Faqat Command/Query yuboradi
├── Requests/           — FormRequest (validation)
├── Resources/          — API Response transformer
└── routes/
    └── api.php
```

---

## Naming Conventions

### Fayl va Papka

```
Module:         PascalCase, suffixsiz       → Order/, Auth/, Cart/
Entity:         PascalCase                  → Order, OrderItem
ValueObject:    PascalCase                  → Money, DeliveryAddress
Enum:           PascalCase + Enum           → OrderStatusEnum, PaymentStatusEnum
Command:        PascalCase + Command        → CreateOrderCommand
Query:          PascalCase + Query          → GetOrderByIdQuery
Handler:        PascalCase + Handler        → CreateOrderHandler
DTO:            PascalCase + DTO            → CreateOrderDTO
Exception:      PascalCase + Exception      → InsufficientStockException
Interface:      PascalCase + Repository     → OrderRepositoryInterface
Implementation: Eloquent + ...Repository   → EloquentOrderRepository
Controller:     PascalCase + Controller     → OrderController
Request:        Action + PascalCase + Req   → CreateOrderRequest
Resource:       PascalCase + Resource       → OrderResource
```

### Database

```
Jadval:         snake_case, ko'plik         → orders, order_items, cart_items
Primary key:    auto-increment bigint       → id
Foreign key:    {singular}_id              → order_id, product_id, user_id
Timestamps:     created_at, updated_at     (Laravel default, hamma jadvalda)
Soft delete:    deleted_at                 (faqat kerak bo'lganda)
Status:         string + Enum cast         → status ('pending', 'paid', ...)
Boolean:        is_ prefiksi               → is_active, is_verified
Counter:        _count suffiksi            → not_found_count
```

### Kod

```php
Handler metodi:         handle()
DTO factory (request):  CreateOrderDTO::fromRequest($request)
DTO factory (array):    CreateOrderDTO::fromArray($data)
Repository metodlari:   find(), findById(), findAll(), save(), delete()
DomainService:          OrderDomainService::canBeCancelled($order)
```

---

## Command Bus (Package Yo'q — DI Orqali)

```php
// ✅ Controller — Handler ni inject qilib chaqiradi
class OrderController {
    public function __construct(
        private readonly CreateOrderHandler  $createHandler,
        private readonly GetOrderByIdQuery   $getQuery,
    ) {}

    public function store(CreateOrderRequest $request): JsonResponse {
        $order = $this->createHandler->handle(
            CreateOrderCommand::fromRequest($request)
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma yaratildi'])
            ->response()
            ->setStatusCode(201);
    }
}

// ✅ Handler — biznes logika bu yerda
class CreateOrderHandler {
    public function __construct(
        private readonly OrderRepositoryInterface   $orders,
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(CreateOrderCommand $command): Order {
        // faqat biznes logika — HTTP bilmaydi
    }
}
```

---

## API Response Format

**Qoida:** Har doim to'g'ri HTTP status kodi bilan qaytariladi.
`response()->json(...)` ishlatganda status kod **har doim** ikkinchi argument.

---

### 200 — GET / PUT / PATCH muvaffaqiyat

```json
HTTP/1.1 200 OK

{
  "data": { "id": 1, "status": "confirmed", "total": 150000 },
  "message": "Buyurtma tasdiqlandi"
}
```

```php
return OrderResource::make($order)
    ->additional(['message' => 'Buyurtma tasdiqlandi'])
    ->response()
    ->setStatusCode(200);
```

---

### 201 — POST (yangi resurs yaratildi)

```json
HTTP/1.1 201 Created

{
  "data": { "id": 5, "status": "pending", "total": 150000 },
  "message": "Buyurtma yaratildi"
}
```

```php
return OrderResource::make($order)
    ->additional(['message' => 'Buyurtma yaratildi'])
    ->response()
    ->setStatusCode(201);
```

---

### 200 — Ro'yxat + Pagination (GET collection)

```json
HTTP/1.1 200 OK

{
  "data": [ {...}, {...} ],
  "meta": {
    "current_page": 1,
    "per_page":     15,
    "total":        100,
    "last_page":    7
  }
}
```

```php
// meta avtomatik qo'shiladi
return OrderResource::collection($orders->paginate(15));
// status 200 — default
```

---

### 204 — DELETE (content yo'q)

```
HTTP/1.1 204 No Content
(body bo'sh)
```

```php
return response()->noContent(); // 204
```

---

### 401 — Autentifikatsiya yo'q

```json
HTTP/1.1 401 Unauthorized

{
  "message": "Autentifikatsiya talab qilinadi"
}
```

```php
// Sanctum / guard avtomatik qaytaradi
// Yoki qo'lda:
return response()->json(
    ['message' => 'Autentifikatsiya talab qilinadi'],
    401
);
```

---

### 403 — Ruxsat yo'q

```json
HTTP/1.1 403 Forbidden

{
  "message": "Ruxsat yo'q"
}
```

```php
return response()->json(['message' => 'Ruxsat yo\'q'], 403);
// yoki
abort(403, 'Ruxsat yo\'q');
```

---

### 404 — Topilmadi

```json
HTTP/1.1 404 Not Found

{
  "message": "Buyurtma topilmadi"
}
```

```php
// findOrFail() avtomatik ModelNotFoundException → 404
$order = Order::findOrFail($id);

// app/Exceptions/Handler.php da map qilingan:
// ModelNotFoundException → 404
```

---

### 422 — Validation xato (FormRequest)

```json
HTTP/1.1 422 Unprocessable Entity

{
  "message": "Ma'lumotlar noto'g'ri",
  "errors": {
    "phone":    ["Telefon raqam noto'g'ri formatda"],
    "quantity": ["Miqdor 1 dan kichik bo'lishi mumkin emas"]
  }
}
```

```php
// FormRequest avtomatik qaytaradi — qo'shimcha kod shart emas
// Barcha Validation xatolar 422 bilan keladi
```

---

### 422 — Biznes logika xatosi

```json
HTTP/1.1 422 Unprocessable Entity

{
  "message": "Mahsulot yetarli emas"
}
```

```php
// Handler da exception tashlaydi
throw new InsufficientStockException('Mahsulot yetarli emas');

// app/Exceptions/Handler.php da render:
$exceptions->render(function (InsufficientStockException $e) {
    return response()->json(['message' => $e->getMessage()], 422);
});

// Barcha domain exception lar shu yerda map qilinadi:
// InsufficientStockException  → 422
// InvalidOtpException         → 422
// OtpRateLimitException       → 429
// ModelNotFoundException       → 404
```

---

### 429 — Too Many Requests (OTP rate limit)

```json
HTTP/1.1 429 Too Many Requests

{
  "message": "Juda ko'p urinish. 10 daqiqadan so'ng qayta urinib ko'ring."
}
```

```php
$exceptions->render(function (OtpRateLimitException $e) {
    return response()->json(['message' => $e->getMessage()], 429);
});
```

---

### 500 — Tizim xatosi

```json
HTTP/1.1 500 Internal Server Error

{
  "message": "Tizim xatosi yuz berdi"
}
```

```php
// app/Exceptions/Handler.php da global catch:
// Production da stack trace ko'rsatilmaydi
// Faqat generic message qaytariladi
```

---

### HTTP Status Kodlar — To'liq Jadval

```
200 — GET muvaffaqiyat, PUT/PATCH muvaffaqiyat
201 — POST (yangi resurs yaratildi)
204 — DELETE (body yo'q)
401 — Token yo'q yoki muddati o'tgan
403 — Ruxsat yo'q (guard/role mos kelmadi)
404 — Resurs topilmadi (findOrFail)
422 — Validation xato yoki biznes logika xatosi
429 — Rate limit (OTP, API throttle)
500 — Kutilmagan tizim xatosi
```

---

## Qat'iy Qoidalar

### 1. Controller faqat yo'naltiradi — logika yo'q

```php
// ❌ Noto'g'ri
public function store(Request $request) {
    $product = Product::find($request->product_id);
    if ($product->stock < $request->qty) {
        return response()->json(['message' => 'Yetarli emas'], 422);
    }
    Order::create([...]);
}

// ✅ To'g'ri
public function store(CreateOrderRequest $request): JsonResponse {
    $order = $this->handler->handle(
        CreateOrderCommand::fromRequest($request)
    );
    return OrderResource::make($order)
        ->additional(['message' => 'Buyurtma yaratildi'])
        ->response()->setStatusCode(201);
}
```

### 2. Modullar faqat Event yoki Interface orqali bog'lanadi

```php
// ❌ Noto'g'ri — to'g'ridan import
use Modules\Product\Infrastructure\Persistence\Models\Product;

// ✅ To'g'ri — Laravel Event
event(new OrderPaid($orderId));

// ✅ To'g'ri — Interface orqali DI
public function __construct(
    private readonly ProductRepositoryInterface $products,
) {}
```

### 3. Strict types — hamma PHP faylda birinchi qator

```php
<?php
declare(strict_types=1);
```

### 4. Stock — SELECT FOR UPDATE majburiy

```php
DB::transaction(function () use ($id, $quantity) {
    $product = Product::lockForUpdate()->findOrFail($id);
    if ($product->stock < $quantity) {
        throw new InsufficientStockException('Mahsulot yetarli emas');
    }
    // order yaratiladi, stock HALI kamaytirilmaydi
    // stock faqat payment webhook da kamayadi
});
```

### 5. Payment webhook — Idempotency

```php
if (Payment::where('transaction_id', $txId)->where('status', 'paid')->exists()) {
    return response()->json(['status' => 'ok']); // duplicate → skip
}
```

### 6. SMS / Telegram — Queue orqali (async)

```php
// ❌ Webhook ichida to'g'ridan
SmsService::send($phone, $message);

// ✅ Queue ga tashla
dispatch(new SendSmsJob($phone, $message));
dispatch(new SendTelegramJob($chatId, $message));
```

### 7. OTP xavfsizlik

```
Amal muddati:    120 soniya
Max urinish:     5 ta (noto'g'ri kod)
5 dan keyin:     10 daqiqa blok (IP + telefon bo'yicha)
```

---

## Modul Tartibi (Qadamma-Qadam)

```
1.  Auth          — OTP, rate limiting, Sanctum token
2.  User          — Profil, Address
3.  Category      — CRUD
4.  Product       — Approval oqimi (manager → admin → active)
5.  Cart          — Persistent savatcha (DB da)
6.  Order         — SELECT FOR UPDATE, status machine
7.  Payment       — Webhook + idempotency (Payme, Click, Uzum)
8.  Review        — Moderation queue
9.  Courier       — Topilmadi oqimi (3× → delivery_issue)
10. Admin/*       — Auth, Order, Product, Courier, Review, User, Transaction, Settings
```

**Har modul ichida tartib:** `Domain → Application → Infrastructure → Presentation`
Controller eng oxirida yoziladi.

---

## Guard Mapping

```
api         → Customer    (Sanctum token, OTP login)
manager     → Manager     (Email + parol)
courier     → Courier     (Email + parol)
admin       → Admin       (Email + parol)
super_admin → Super Admin (Email + parol)
```

---

## Buyurtma Status Machine

```
pending          → paid              (to'lov webhook)
pending          → cancelled         (customer, to'lovsiz)
paid             → confirmed         (manager qabul qiladi)
confirmed        → ready_to_deliver  (manager yig'ib topshiradi)
ready_to_deliver → delivering        (kuryer qabul qiladi)
delivering       → delivered         (kuryer topshiradi)
delivering       → delivery_issue    (3× topilmadi)
delivery_issue   → delivering        (admin qayta rejalashtiradi)
delivery_issue   → cancelled         (admin + mijoz kelishuvida)
```

---

## Fayl Joylashuvi — Qat'iy Qoidalar

```
❌ HECH QACHON bu joylar ishlatilmaydi:
   routes/api.php          → Modules ichida bo'ladi
   routes/web.php          → Modules ichida bo'ladi
   database/migrations/    → Modules ichida bo'ladi
   app/Models/             → Modules ichida bo'ladi
   app/Http/Controllers/   → Modules ichida bo'ladi
   app/Http/Requests/      → Modules ichida bo'ladi

✅ TO'G'RI JOYLAR:
   Route      → Modules/{Name}/Presentation/routes/api.php
   Migration  → Modules/{Name}/Infrastructure/Persistence/Migrations/
   Model      → Modules/{Name}/Infrastructure/Persistence/Models/
   Controller → Modules/{Name}/Presentation/Controllers/
   Request    → Modules/{Name}/Presentation/Requests/
   Resource   → Modules/{Name}/Presentation/Resources/
```

Route va Migration lar avtomatik yuklanadi:
- Route: bootstrap/app.php da glob pattern orqali
- Migration: AppServiceProvider.php da glob pattern orqali
Yangi modul qo'shilganda hech qayerga tegish shart emas.

## STRUCTURE.md — Yangilash Qoidasi

Har fayl yozilgandan keyin `STRUCTURE.md` yangilanadi:
- Yangi fayl qo'shilsa    → tegishli modulga qo'shiladi
- Yangi modul yaratilsa   → yangi modul bo'limi ochiladi
- Fayl o'chirilsa         → strukturadan o'chiriladi