# Arxitektura Qo'llanmasi — Modular DDD

> Bu fayl loyihada ishlaydigan **har bir developer va AI agent** uchun majburiy o'qish hujjati.
> Kod yozishdan oldin bu faylni to'liq o'qing.
> Arxitekturadan chetga chiqish Pull Request da rad etiladi.

---

## Mundarija

1. [DDD Nima?](#1-ddd-nima)
2. [Bizning Yondashuv](#2-bizning-yondashuv)
3. [Loyiha Tuzilishi](#3-loyiha-tuzilishi)
4. [Har bir Qatlam — Aniq Vazifa](#4-har-bir-qatlam--aniq-vazifa)
5. [Global Mexanizmlar](#5-global-mexanizmlar)
6. [Response Standarti](#6-response-standarti)
7. [Yangi Modul Yaratish — Ketma-ket](#7-yangi-modul-yaratish--ketma-ket)
8. [Kod Qoidalari](#8-kod-qoidalari)
9. [Keng Tarqalgan Xatolar](#9-keng-tarqalgan-xatolar)

---

## 1. DDD Nima?

**Domain-Driven Design (DDD)** — katta va murakkab loyihalarni boshqarish uchun mo'ljallangan arxitektura yondashuvi.

### Asosiy g'oya

Kodingizni **biznes domainlari** (sohalari) atrofida tashkil qiling — texnik qatlamlar atrofida emas.

#### Oddiy misol bilan tushuntirish

Klassik Laravel da (texnik qatlamlar bo'yicha):
```
app/
├── Controllers/
│   ├── AuthController.php
│   ├── UserController.php
│   └── OrderController.php
├── Models/
│   ├── User.php
│   └── Order.php
└── Services/
    ├── AuthService.php
    └── OrderService.php
```

**Muammo:** Auth bilan bog'liq hamma narsa turli papkalarda tarqalib ketgan. Bitta funksiyani o'zgartirish uchun 5 ta papkani ko'rib chiqish kerak.

DDD da (domain bo'yicha):
```
Modules/
├── Auth/
│   ├── Controllers/
│   ├── Services/
│   ├── Models/
│   └── ...
└── Order/
    ├── Controllers/
    ├── Services/
    ├── Models/
    └── ...
```

**Afzalligi:** Auth bilan bog'liq hamma narsa `Modules/Auth/` ichida. Bitta joyda — bitta mas'uliyat.

### DDD ning asosiy ustunliklari

| Muammo | DDD Yechimi |
|--------|-------------|
| Kod tarqalib ketadi | Har bir domain o'z papkasida |
| O'zgartirish qiyin | Modul mustaqil — boshqasiga ta'sir qilmaydi |
| Jamoa bilan ishlash qiyin | Har bir developer o'z modulida ishlaydi |
| Test yozish qiyin | Har bir qatlam alohida test qilinadi |
| Loyiha o'sishi bilan chalkashib ketadi | Modul tuzilishi har doim bir xil |

### Bizning DDD — Modular Monolith

Biz **to'liq DDD emas**, balki **Modular Monolith** ishlatamiz. Bu ikki dunyoning eng yaxshi tomoni:

- ✅ DDD dan: domain bo'yicha tashkil etish, qatlamli arxitektura
- ✅ Monolithdan: oddiy deployment, bitta database, bitta Laravel ilovasi
- ✅ Kelajakda kerak bo'lsa microservicega o'tish oson

---

## 2. Bizning Yondashuv

### Qatlamlar va ma'suliyat

```
HTTP Request
     │
     ▼
┌─────────────┐
│  Middleware │  ← ForceJsonResponse: har doim JSON qaytaradi
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Request    │  ← Faqat validation. BaseRequest dan extends.
└──────┬──────┘
       │ validated data
       ▼
┌─────────────┐
│ Controller  │  ← Faqat Request → DTO → Service → Response.
└──────┬──────┘   try/catch YO'Q — Handler avtomatik tutadi.
       │ DTO
       ▼
┌─────────────┐
│   Service   │  ← BARCHA biznes logika shu yerda.
└──────┬──────┘   Exception tashlaydi → Handler tutadi.
       │ Model / array
       ▼
┌─────────────┐
│    Model    │  ← Faqat database bilan ishlash.
└─────────────┘
       │
       ▼
┌─────────────┐
│  Resource   │  ← Model → JSON formatiga o'girish.
└─────────────┘
       │
       ▼
┌─────────────┐
│  Handler    │  ← Exception larni tutib ApiResponse qaytaradi.
└─────────────┘
       │
       ▼
HTTP Response (ApiResponse standarti)
```

### Ma'lumot oqimi — qisqacha

1. **Middleware** — `Accept: application/json` header qo'yadi
2. **Request** keladi → **BaseRequest** validation qiladi
3. **Controller** validated ma'lumotni **DTO** ga o'giradi
4. **Controller** DTOni **Service** ga uzatadi — `try/catch` YO'Q
5. **Service** biznes logikani bajaradi, **Model** bilan ishlaydi
6. Xato bo'lsa **Service** exception tashlaydi → **Handler** tutadi
7. **Controller** natijani **Resource** orqali **ApiResponse** bilan qaytaradi

---

## 3. Loyiha Tuzilishi

### Modul tuzilishi

```
Modules/
└── {ModuleName}/
    ├── Controllers/        ← HTTP qatlami
    │   └── {Name}Controller.php
    ├── DTOs/               ← Ma'lumot uzatish obyektlari
    │   └── {Action}DTO.php
    ├── Enums/              ← Konstantalar va holat ro'yxatlari
    │   └── {Name}Enum.php
    ├── Models/             ← Eloquent modellari
    │   └── {Name}.php
    ├── Requests/           ← Validation qoidalari
    │   └── {Action}Request.php
    ├── Resources/          ← API response formatlash
    │   └── {Name}Resource.php
    ├── Services/           ← Biznes logika
    │   └── {Name}Service.php
    └── routes/             ← API marshrutlari
        └── api.php
```

### Umumiy (Shared) qismlar

```
app/
├── Exceptions/
│   └── Handler.php              ← Global exception handler
├── Http/
│   ├── Middleware/
│   │   └── ForceJsonResponse.php ← Har doim JSON qaytarish
│   └── Requests/
│       └── BaseRequest.php      ← Barcha Request lar shu dan extends
├── Providers/
│   └── AppServiceProvider.php   ← Modul route larini ro'yxatdan o'tkazish
└── Shared/
    └── Services/
        ├── SMS/SmsService.php
        ├── Telegram/TelegramService.php
        └── Payment/PaymentService.php
```

### Namespace qoidasi

Fayl joylashuvi va namespace **mos** bo'lishi shart:

```php
// Fayl: Modules/Auth/Controllers/AuthController.php
namespace Modules\Auth\Controllers;

// Fayl: Modules/Order/Services/OrderService.php
namespace Modules\Order\Services;

// Fayl: app/Http/Requests/BaseRequest.php
namespace App\Http\Requests;

// Fayl: app/Exceptions/Handler.php
namespace App\Exceptions;
```

---

## 4. Har bir Qatlam — Aniq Vazifa

---

### 4.1 BaseRequest — Asosiy Request Sinfi

`app/Http/Requests/BaseRequest.php` — bir marta yozilgan, o'zgartirmang.

**Vazifasi:** `authorize()` ni bir martalik yozish — barcha Request larda takrorlanmasin.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Har doim true. Ruxsat middleware da hal qilinadi.
    }
}
```

---

### 4.2 FormRequest — Validation

**Faqat bitta vazifa:** incoming ma'lumotni tekshirish.

**Qoida:** Har doim `BaseRequest` dan extends qiling — `FormRequest` emas!

**Ichida nima bo'ladi:**
- `rules()` — validation qoidalari
- `messages()` — xato xabarlari (o'zbek tilida)

**Ichida nima bo'lmaydi:**
- ❌ `authorize()` — `BaseRequest` da bor, takrorlamang
- ❌ Biznes logika
- ❌ Database so'rovlari (faqat `exists`, `unique` bundan mustasno)
- ❌ Service chaqiruvi

```php
<?php

namespace Modules\{ModuleName}\Requests;

use App\Http\Requests\BaseRequest;

// ✅ TO'G'RI — BaseRequest dan extends, authorize() yo'q
class CreateSomethingRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+998[0-9]{9}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Ism kiritilishi shart.',
            'phone.required' => 'Telefon raqami kiritilishi shart.',
            'phone.regex'    => 'Telefon raqami +998XXXXXXXXX formatida bo\'lishi kerak.',
        ];
    }
}

// ❌ NOTO'G'RI — FormRequest dan extends va authorize() takrorlanmoqda
class CreateSomethingRequest extends FormRequest
{
    public function authorize(): bool { return true; } // ← keraksiz takror
}
```

---

### 4.3 DTO — Ma'lumot Uzatish Obyekti

**Faqat bitta vazifa:** validated ma'lumotni Controller dan Service ga type-safe tarzda uzatish.

**Nima uchun array emas DTO?**

```php
// ❌ array bilan — nima borligini bilmaysan, typo bo'lishi mumkin
$service->create($request->validated());

// ✅ DTO bilan — IDE ko'rsatadi, type-safe, aniq
$service->create(CreateSomethingDTO::fromArray($request->validated()));
```

**Ichida nima bo'ladi:**
- `readonly` propertylar — o'zgarmaslik kafolati
- `fromArray()` static metod — array dan DTO yasash

**Ichida nima bo'lmaydi:**
- ❌ Biznes logika
- ❌ Database so'rovlari
- ❌ Validation

```php
<?php

namespace Modules\{ModuleName}\DTOs;

class CreateSomethingDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $phone,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            phone:       $data['phone'],
            description: $data['description'] ?? null,
        );
    }
}
```

---

### 4.4 Controller — HTTP Qatlami

**Faqat bitta vazifa:** Request qabul qilish, Service ga uzatish, Response qaytarish.

**Ichida nima bo'ladi:**
- Constructor da Service inject qilish
- Request → DTO → Service chaqiruvi
- `ApiResponse::success()` qaytarish

**Ichida nima bo'lmaydi:**
- ❌ `try/catch` — Global Handler avtomatik tutadi
- ❌ Biznes logika
- ❌ Database so'rovlari (`User::find()`, `DB::table()`)
- ❌ Redis, Mail, SMS chaqiruvi
- ❌ 50 qatordan ortiq metod (fat controller belgisi)

```php
<?php

namespace Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\{ModuleName}\DTOs\CreateSomethingDTO;
use Modules\{ModuleName}\Requests\CreateSomethingRequest;
use Modules\{ModuleName}\Resources\SomethingResource;
use Modules\{ModuleName}\Services\{ModuleName}Service;

class {ModuleName}Controller extends Controller
{
    public function __construct(
        private readonly {ModuleName}Service $service,
    ) {}

    // ✅ TO'G'RI — try/catch yo'q, toza va sodda
    public function store(CreateSomethingRequest $request): JsonResponse
    {
        $result = $this->service->create(
            CreateSomethingDTO::fromArray($request->validated())
        );

        return ApiResponse::success(
            data: new SomethingResource($result),
            message: 'Muvaffaqiyatli yaratildi.',
            code: 201
        );
    }

    // ❌ NOTO'G'RI — try/catch bor, logika bor
    public function store(CreateSomethingRequest $request): JsonResponse
    {
        try {
            $item = Something::create($request->all()); // ← model to'g'ridan
            return response()->json($item);              // ← Resource ishlatilmagan
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500); // ← ApiResponse yo'q
        }
    }
}
```

---

### 4.5 Service — Biznes Logika

**Faqat bitta vazifa:** Barcha biznes logika.

**Ichida nima bo'ladi:**
- Database operatsiyalari (Model orqali)
- Redis, Mail, SMS chaqiruvi
- Boshqa Service larni chaqirish
- Hisob-kitoblar, tekshiruvlar
- Exception tashlash

**Ichida nima bo'lmaydi:**
- ❌ `Request` obyekti qabul qilish
- ❌ `JsonResponse` qaytarish
- ❌ `response()->json()` chaqiruvi

**Exception tashlash qoidasi:**

```php
// HTTP statusini exception code sifatida ishlating
throw new \Exception('Topilmadi.', 404);
throw new \Exception('Ruxsat yo\'q.', 403);
throw new \Exception('Noto\'g\'ri ma\'lumot.', 422);
throw new \Exception('Juda ko\'p urinish.', 429);
// Code qo'yilmasa — Handler 500 qaytaradi
```

```php
<?php

namespace Modules\{ModuleName}\Services;

use Modules\{ModuleName}\DTOs\CreateSomethingDTO;
use Modules\{ModuleName}\Models\Something;

class {ModuleName}Service
{
    public function create(CreateSomethingDTO $dto): Something
    {
        $exists = Something::where('phone', $dto->phone)->exists();
        if ($exists) {
            throw new \Exception('Bu telefon raqam allaqachon mavjud.', 422);
        }

        return Something::create([
            'name'  => $dto->name,
            'phone' => $dto->phone,
        ]);
    }

    public function findOrFail(int $id): Something
    {
        $item = Something::find($id);

        if (! $item) {
            throw new \Exception('Topilmadi.', 404);
        }

        return $item;
    }
}
```

---

### 4.6 Model — Database Qatlami

**Faqat bitta vazifa:** Eloquent orqali database bilan ishlash.

**Ichida nima bo'ladi:**
- `$fillable` — mass assignment uchun ruxsat berilgan maydonlar
- `$hidden` — JSON da yashiriladigan maydonlar
- `$casts` — avtomatik type conversion
- Relationlar (`hasMany`, `belongsTo`)
- Local scope lar

**Ichida nima bo'lmaydi:**
- ❌ Biznes logika
- ❌ HTTP so'rovlari

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Model;

class Something extends Model
{
    protected $fillable = ['name', 'phone', 'status'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
```

---

### 4.7 Resource — Response Formatlash

**Faqat bitta vazifa:** Model ma'lumotlarini API response uchun formatlash.

**Ichida nima bo'ladi:**
- Faqat kerakli maydonlar
- Sana formatlash
- Nested resource lar

**Ichida nima bo'lmaydi:**
- ❌ Biznes logika
- ❌ `password`, `remember_token` kabi maxfiy maydonlar

```php
<?php

namespace Modules\{ModuleName}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SomethingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
```

---

### 4.8 Enum — Konstantalar

```php
<?php

namespace Modules\{ModuleName}\Enums;

enum StatusEnum: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
    case PENDING  = 'pending';
}

// Ishlatish
Something::where('status', StatusEnum::ACTIVE->value)->get();
```

---

### 4.9 Routes — API Marshrutlari

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\{ModuleName}\Controllers\{ModuleName}Controller;

// Himoyalangan routelar (token kerak)
Route::middleware('auth:sanctum')->prefix('{module-prefix}')->group(function () {
    Route::get('/',        [{ModuleName}Controller::class, 'index']);
    Route::post('/',       [{ModuleName}Controller::class, 'store']);
    Route::get('/{id}',    [{ModuleName}Controller::class, 'show']);
    Route::put('/{id}',    [{ModuleName}Controller::class, 'update']);
    Route::delete('/{id}', [{ModuleName}Controller::class, 'destroy']);
});

// Ochiq routelar (token shart emas)
Route::prefix('{module-prefix}')->group(function () {
    Route::get('/', [{ModuleName}Controller::class, 'index']);
});
```

---

## 5. Global Mexanizmlar

Bu qismlar bir marta yozilgan — **o'zgartirmang**, faqat qanday ishlashini biling.

---

### 5.1 ForceJsonResponse Middleware

**Fayl:** `app/Http/Middleware/ForceJsonResponse.php`

**Vazifasi:** Har qanday so'rovga `Accept: application/json` header qo'yadi. Natijada Laravel har doim JSON qaytaradi — HTML emas.

**Nima uchun kerak?**
```
// Middleware bo'lmasa — brauzerdan kirilganda HTML 404 sahifasi keladi
// Middleware bilan — har doim JSON keladi
{
    "success": false,
    "message": "Endpoint topilmadi.",
    "data": null
}
```

**Qayerda ro'yxatdan o'tgan:** `bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
})
```

---

### 5.2 Global Exception Handler

**Fayl:** `app/Exceptions/Handler.php`

**Vazifasi:** Butun loyihadagi barcha exception larni tutib `ApiResponse` formatida qaytaradi.

**Nima uchun kerak?**

```php
// Handler bo'lmasa — har controller da takrorlanardi:
} catch (\Exception $e) {
    $code = $e->getCode();
    $httpCode = ($code >= 400 && $code < 600) ? $code : 500;
    return ApiResponse::error($e->getMessage(), $httpCode);
}

// Handler bilan — controller toza, bir qator ham yo'q:
public function store(CreateSomethingRequest $request): JsonResponse
{
    $result = $this->service->create(...); // xato bo'lsa Handler tutadi
    return ApiResponse::success(data: new SomethingResource($result));
}
```

**Qaysi exception larni tutadi:**

| Exception | Sabab | HTTP |
|-----------|-------|------|
| `ValidationException` | FormRequest validation | 422 |
| `AuthenticationException` | Token yo'q yoki noto'g'ri | 401 |
| `ModelNotFoundException` | `findOrFail()` topilmadi | 404 |
| `NotFoundHttpException` | Route topilmadi | 404 |
| `HttpException` | `abort()` chaqirilgan | * |
| `\Exception` | Service dan kelgan | code bo'yicha |

**Keyinchalik kerak bo'lishi mumkin (hozir yo'q — YAGNI):**
- `QueryException` — database xatosi
- `MethodNotAllowedHttpException` — noto'g'ri HTTP method

---

### 5.3 AppServiceProvider — Modul Route lari

**Fayl:** `app/Providers/AppServiceProvider.php`

**Vazifasi:** Barcha modul route fayllarini Laravel ga ro'yxatdan o'tkazadi.

**Yangi modul qo'shilganda bu yerga qo'shish SHART:**
```php
private function registerModuleRoutes(): void
{
    $modules = [
        'Auth',
        'User',
        'Product',
        'Cart',
        'Order',
        // Yangi modul shu yerga
    ];
    // ...
}
```

---

## 6. Response Standarti

**Barcha** API response quyidagi formatda bo'ladi. Istisnosiz.

### Muvaffaqiyatli response

```json
{
    "success": true,
    "message": "Muvaffaqiyatli bajarildi.",
    "data": { }
}
```

### Xato response

```json
{
    "success": false,
    "message": "Foydalanuvchi topilmadi.",
    "data": null
}
```

### Validation xato response

```json
{
    "success": false,
    "message": "Validation xatosi.",
    "data": {
        "phone": ["Telefon raqami kiritilishi shart."],
        "name":  ["Ism kiritilishi shart."]
    }
}
```

### ApiResponse ishlatish

```php
use App\Shared\Responses\ApiResponse;

// Ma'lumot bilan (200)
return ApiResponse::success(
    data: new UserResource($user),
    message: 'Profil olindi.'
);

// Ma'lumotsiz (200)
return ApiResponse::success(message: 'O\'chirildi.');

// Yaratish (201)
return ApiResponse::success(
    data: new SomethingResource($item),
    message: 'Yaratildi.',
    code: 201
);

// Xato
return ApiResponse::error(message: 'Topilmadi.', code: 404);
```

---

## 7. Yangi Modul Yaratish — Ketma-ket

Bu qadamlarni **tartib bilan** bajaring. Birontasini o'tkazib yubormang.

### Qadam 1 — Papka tuzilishi

```bash
mkdir -p Modules/{ModuleName}/{Controllers,DTOs,Enums,Models,Requests,Resources,Services,routes}
```

### Qadam 2 — AppServiceProvider ga qo'shing ⚠️

> **Bu qadam o'tkazilsa route lar ishlamaydi! 404 xatosi keladi.**
> Sababi: Laravel route fayllarini avtomatik topib olmaydi.

```php
// app/Providers/AppServiceProvider.php
$modules = [
    'Auth',
    'User',
    'YangiModul', // ← shu yerga qo'shing
];
```

### Qadam 3 — Route fayli yarating

```bash
touch Modules/{ModuleName}/routes/api.php
```

### Qadam 4 — Zarur fayllarni yarating

Controller, Service, Model, Resource, Request, DTO — yuqoridagi shablonlarga asosan.

**Eslatma:**
- Request → `BaseRequest` dan extends (FormRequest emas!)
- Controller → `try/catch` yo'q

### Qadam 5 — Migration yarating va ishga tushiring

```bash
docker exec app php artisan make:migration create_{table_name}_table
docker exec app php artisan migrate
```

### Qadam 6 — Autoload yangilang ⚠️

```bash
docker exec app composer dump-autoload
```

> **Bu qadam o'tkazilsa yangi sinflar topilmaydi!**
> Sababi: PHP yangi fayl qo'shilganida avtomatik bilmaydi.

---

## 8. Kod Qoidalari

### Nomlash

```
Controller  → {ModuleName}Controller.php
Service     → {ModuleName}Service.php
Model       → {ModelName}.php              (birlik: User, Product, Order)
DTO         → {Action}{Context}DTO.php     (CreateOrderDTO, UpdateProfileDTO)
Request     → {Action}{Context}Request.php
Resource    → {ModelName}Resource.php
Enum        → {Name}Enum.php               (OrderStatusEnum, OtpTypeEnum)
```

### Redis ishlatish

```php
// ❌ Static call — ishlamaydi
Redis::setex($key, 120, $value);

// ✅ Connection orqali
use Illuminate\Support\Facades\Redis;
Redis::connection()->setex($key, 120, $value);
Redis::connection()->get($key);
Redis::connection()->del($key);
```

### Exception tashlash

```php
// ✅ HTTP status code bilan — Handler to'g'ri response qaytaradi
throw new \Exception('Topilmadi.', 404);
throw new \Exception('Ruxsat yo\'q.', 403);
throw new \Exception('Noto\'g\'ri ma\'lumot.', 422);
throw new \Exception('Juda ko\'p urinish.', 429);

// ❌ Code siz — 500 qaytadi, debug qiyin
throw new \Exception('Topilmadi.');
```

---

## 9. Keng Tarqalgan Xatolar

### ❌ Xato 1: Route topilmaydi (404)

**Belgi:**
```json
{"success": false, "message": "Endpoint topilmadi."}
```
**Sabab:** Yangi modul `AppServiceProvider` ga qo'shilmagan.
**Yechim:** `$modules` massiviga modul nomini qo'shing.

---

### ❌ Xato 2: Sinf topilmaydi

**Belgi:**
```
Target class [Modules\X\Controllers\XController] does not exist.
```
**Sabab:** `composer dump-autoload` qilinmagan.
**Yechim:**
```bash
docker exec app composer dump-autoload
```

---

### ❌ Xato 3: Namespace xatosi

**Belgi:**
```
Class "Modules\User\Services\UserService" not found
```
**Sabab:** Fayl joylashuvi va namespace mos kelmaydi.
**Yechim:** Fayl `Modules/User/Services/UserService.php` bo'lsa:
```php
namespace Modules\User\Services; // ← aynan shu
```

---

### ❌ Xato 4: Nullable migration

**Belgi:**
```
SQLSTATE[23000]: Column 'name' cannot be null
```
**Sabab:** Migration da `nullable()` qo'yilmagan.
**Yechim:**
```php
$table->string('name')->nullable();
```

---

### ❌ Xato 5: Noto'g'ri HTTP status kodi

**Belgi:**
```
The HTTP status code "23000" is not valid.
```
**Sabab:** MySQL xatosi kodi HTTP status sifatida ishlatilgan.
**Yechim:** Exception da to'g'ri HTTP kodi ishlating:
```php
throw new \Exception('Xabar.', 422); // 400-599 orasida
```

---

### ❌ Xato 6: Fat Controller

**Belgi:** Controller metodi 30+ qator, database so'rovlari bor, try/catch bor.
**Sabab:** Biznes logika Controller da yozilgan.
**Yechim:** Barcha logikani Service ga ko'chiring. Controller faqat:
1. Request → DTO
2. DTO → Service
3. Natija → ApiResponse

---

### ❌ Xato 7: Redis static call

**Belgi:**
```
Non-static method Redis::set() cannot be called statically.
```
**Yechim:**
```php
Redis::connection()->set($key, $value);
```

---

### ❌ Xato 8: authorize() takrorlash

**Belgi:** Har bir Request da `authorize(): bool { return true; }` yozilgan.
**Sabab:** `FormRequest` dan extends qilingan, `BaseRequest` dan emas.
**Yechim:**
```php
// ❌
class MyRequest extends FormRequest { ... }

// ✅
class MyRequest extends BaseRequest { ... }
```

---

> **Yakuniy eslatma:**
> Har qanday noaniqlikda — kodni o'zgartirmasdan oldin bu faylni qayta o'qing.
> Agar bu faylda javob topilmasa — Pull Request ochib savol bering.