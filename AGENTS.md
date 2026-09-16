# Uvita Backend - agent qoidalari

Ushbu fayl Uvita backendida kod yozadigan AI agent va dasturchilar uchun majburiy
texnik qoida. Biznes qarorlar bu faylda takrorlanmaydi.

## Har bir ish boshida o'qilsin

1. `LIFECYCLE.md` - yagona va ustuvor biznes lifecycle.
2. `STRUCTURE.md` - modul chegaralari va repo tuzilishi.
3. Tegishli modul kodi, migratsiyalari va testlari.
4. `git status` va mavjud diff - foydalanuvchi ishini yo'qotmaslik uchun.

Manbalar ustuvorligi:

1. foydalanuvchining oxirgi aniq talabi;
2. `LIFECYCLE.md`;
3. ushbu `AGENTS.md`;
4. `STRUCTURE.md` va `docs/`;
5. mavjud kod.

Mavjud kod yoki eski hujjat `LIFECYCLE.md`ga zid bo'lsa, eski xulqni ko'r-ko'rona
ko'chirmang. Ziddiyatni qayd eting va yangi lifecycle bo'yicha xavfsiz migratsiya
qiling.

## Hozirgi reliz chegarasi

- Uvita omborsiz marketplace: yuk seller manzilidan xaridorga boradi.
- Birinchi relizda to'lov naqd.
- Online payment va fiskal chek integratsiyasi hozir faol biznes oqimi emas.
- Soliq/fiskal modul uchun interface qoldirish mumkin, lekin real ma'lumot yuborilmaydi.
- Kod butun O'zbekiston uchun umumiy; hudud va reyslar setting orqali yoqiladi.
- Moliyaviy foiz, muddat va limitlar kodga qotirilmaydi; snapshot bilan saqlanadi.

## Texnik stack

- Laravel 12
- PHP `composer.json` talabiga mos; production target PHP 8.4
- MySQL 8
- Redis: cache, queue, rate limit va kerakli vaqtinchalik holatlar
- Laravel Sanctum
- PHPUnit

PHP 8.4-only sintaksisini ishlatishdan oldin `composer.json`, CI va production runtime
hammasi PHP 8.4 ekanini tekshiring. Har PHP faylda `declare(strict_types=1);` bo'lsin.

## Arxitektura

Backend modular monolith va pragmatik DDD usulida ishlaydi:

```text
Modules/{Module}/
├── Domain/          sof biznes qoidalari, enum, VO, event, interface
├── Application/     command/query, DTO va handler
├── Infrastructure/  Eloquent, repository, migration va tashqi adapter
└── Presentation/    controller, request, resource va route
```

Qoidalar:

- Controller faqat requestni qabul qiladi, handler/query chaqiradi va response beradi.
- Biznes qaror controller, resource, job yoki Eloquent observerga yashirilmaydi.
- Domain qatlami HTTP, Eloquent va provider implementationini bilmaydi.
- Modullar event yoki aniq interface orqali bog'lanadi.
- Eloquent model faqat `Infrastructure/Persistence/Models` ichida bo'ladi.
- Route va migration modul ichida bo'ladi; global papkalarga yangi modul fayli yozilmaydi.
- Eski modul borligi uning yangi lifecycle uchun to'g'ri ekanini anglatmaydi.

## Kod sifati

- Nomlar biznes ma'nosini ochiq ifodalasin; qisqartma va noaniq booleanlardan qoching.
- DTO va Value Object imkon qadar immutable/readonly bo'lsin.
- Pul `float` bilan saqlanmaydi yoki hisoblanmaydi. UZS uchun integer yoki aniq decimal ishlating.
- Miqdor, vazn, hajm va foizlarda aniq decimal va markazlashgan rounding qoidasi bo'lsin.
- Vaqt DBda izchil formatda saqlansin; UI `Asia/Tashkent`ga to'g'ri ko'rsatsin.
- Magic number o'rniga typed setting yoki domain constant ishlating.
- Yangi dependency faqat aniq foyda, xavfsizlik va maintenance bahosidan keyin qo'shiladi.
- Eski yoki ishlatilmaydigan kodni kommentda saqlamang; Git tarixida qoladi.

## Database va query performance - qat'iy talab

Query sifati funksional to'g'rilik kabi majburiy. N+1 yoki cheklanmagan og'ir query
tayyor ish hisoblanmaydi.

### N+1ga yo'l qo'yilmaydi

- Collection qaytaradigan har endpointda Resource/transformer ishlatadigan relationlar
  oldindan `with`, `loadMissing`, `withCount`, `withSum` yoki mos agregat bilan olinadi.
- `foreach`, `map`, Resource, accessor, policy yoki Blade/JSON transform ichida yangi DB
  query ochmang.
- Resource relationni faqat `whenLoaded` bilan ishlatsin; Resource DBga murojaat qilmasin.
- Local va test muhitida lazy loading aniqlanishi yoqilgan bo'lsin.
- Collection feature testida kamida representative dataset bilan query count regressiyasi
  tekshirilsin. Itemlar soni oshganda querylar chiziqli oshmasligi kerak.

### Og'ir querylar nazorati

- Barcha list endpointlari pagination bilan; default `15` yoki `20`, maksimum `100`.
- `get()` bilan nazoratsiz katta jadval yuklanmaydi. Batch ishda `chunkById`, `lazyById`
  yoki cursor ishlatiladi.
- Faqat kerakli ustunlarni `select` qiling. Katta media/JSON/text ustunlarni listda olmang.
- Mavjudligini tekshirishda `exists`, son uchun DB agregati ishlating; kolleksiyani olib PHPda
  sanamang.
- Filter, join va sort maydonlari allowlist orqali o'tsin. User kiritgan ustun yoki raw SQL
  to'g'ridan queryga qo'shilmaydi.
- `whereDate`, leading wildcard `%term` va funksiyaga o'ralgan indexed ustunlarning indexni
  o'chirish ta'sirini tekshiring.
- Dashboard agregatlari katta jadvalni har requestda qayta sanamasin; kerak bo'lsa summary,
  cache yoki background projection ishlating.
- Cache faqat aniq invalidation qoidasi bilan qo'shiladi. Cache noto'g'ri queryni yashirish
  vositasi emas.

### Index va query plan

- Har yangi foreign key indexlanadi.
- Ko'p ishlatiladigan filter+sort va joinlar uchun query shakliga mos composite index
  ko'rib chiqiladi. Ustun tartibi real queryga asoslanadi.
- Keraksiz yoki takroriy index qo'shmang; write xarajatini ham baholang.
- Katta yoki muhim query uchun representative ma'lumotda `EXPLAIN`/`EXPLAIN ANALYZE`
  ko'rilsin. Full scan, filesort va vaqtinchalik jadval sababi tushuntirilmasdan merge qilinmaydi.
- Index qo'shilsa, uni talab qilgan endpoint/query va regressiya testi dokument qilinadi.

### Transaction va lock

- Stock, offer accept, delivery confirmation, ledger va withdrawal kabi raqobatli yozuvlar
  `DB::transaction` ichida bajariladi.
- Stock/rezerv uchun `lockForUpdate` majburiy; lock bir xil tartibda olinib deadlock xavfi
  kamaytiriladi.
- Transaction ichida SMS, HTTP, fayl upload yoki boshqa sekin tashqi call bajarilmaydi.
- Transaction imkon qadar qisqa bo'lsin; queue/event faqat commitdan keyin yuborilsin.
- Deadlock va duplicate request uchun xavfsiz retry/idempotency strategiyasi bo'lsin.

### Performance acceptance

Har queryga ta'sir qilgan PR/commit yakunida quyidagilar tekshiriladi:

1. endpoint pagination va limitga ega;
2. N+1 yo'q, relationlar aniq eager loaded;
3. query count test yoki o'lchov bilan nazorat qilingan;
4. kerakli index bor va migratsiya xavfsiz;
5. muhim query plan ko'rilgan;
6. response ortiqcha ustun/media yuklamaydi;
7. katta datasetda memory va vaqt chiziqli portlamaydi.

## Ma'lumot yaxlitligi

- Statuslar string literal sifatida tarqalmasin; enum/state transition service ishlating.
- Ruxsat etilmagan o'tish `422`, ruxsatsiz amal `403` qaytarsin.
- Har muhim transition oldingi holatni tekshirib, atomik bajarilsin.
- Retry bo'lishi mumkin bo'lgan endpoint/job/event idempotent bo'lsin.
- Unique constraint biznes invariantning DB darajasidagi oxirgi himoyasi bo'lsin.
- Delete o'rniga audit talab qilinadigan yozuvlarda soft delete yoki append-only history ishlating.

## Stock qoidasi

- Stock order yaratilganda emas, operator orderni `active` qilgan transactionda rezervlanadi.
- Mahsulot qatorlari `lockForUpdate` bilan deterministik tartibda qulflanadi.
- Yetarli stock bo'lmasa order yashirincha qisman active qilinmaydi.
- Bekor/deactive order rezervni aynan bir marta qaytaradi.
- Payment webhook stockni boshqarmaydi; bu eski lifecycle qoidasi.

## Moliya qoidasi

- Balansning yagona haqiqat manbasi immutable ledger bo'lsin; mutable balance faqat projection.
- Har ledger entryda type, amount, currency, reference, idempotency key va vaqt bo'lsin.
- Seller, platforma va courier summalari alohida yuritiladi.
- Setting foizlari order/delivery yaratilganda snapshot qilinadi.
- Withdrawal mablag'ni transactionda hold qiladi; parallel so'rov double-spend qilmaydi.
- Reversal o'chirish bilan emas, teskari ledger entry va audit bilan qilinadi.

## Xavfsizlik

- Har endpoint authentication, permission va resource ownershipni tekshiradi.
- UI tugmani yashirishi authorization hisoblanmaydi.
- FormRequest validation va Policy/Gate yoki markazlashgan permission ishlating.
- OTP/PIN plain text saqlanmaydi; expiry, retry limit va rate limit majburiy.
- Log/auditda password, token, OTP/PIN, to'liq karta yoki boshqa secret bo'lmaydi.
- Upload MIME, signature, size, extension va access policy bilan tekshiriladi.
- Private dalillar public URL bilan ochilmaydi; vaqtinchalik signed access ishlatiladi.
- Mass assignment, IDOR, SQL injection, unsafe sort/filter va over-posting test qilinadi.

## Queue va tashqi servislar

- SMS, push, Telegram, fiscal/payment provider va boshqa HTTP call adapter ortida bo'ladi.
- Tashqi xabarlar queue orqali, DB commitdan keyin yuboriladi.
- Jobda retry/backoff, timeout, idempotency va failure visibility bo'lsin.
- Provider ishlamasligi asosiy biznes transactionni yo'qotmasin.
- Testda real provider chaqirilmaydi; fake adapter ishlatiladi.

## API standarti

- `200` - muvaffaqiyatli GET/PATCH/PUT
- `201` - yangi resource
- `204` - body'siz muvaffaqiyatli delete
- `401` - autentifikatsiya yo'q yoki token yaroqsiz
- `403` - permission/ownership yo'q
- `404` - resource topilmadi yoki ko'rishga ruxsat bo'lmagan yashirin resource
- `409` - haqiqiy concurrency/version konflikti
- `422` - validation yoki domain qoida buzilishi
- `429` - rate limit
- `500` - productionda ichki tafsilotsiz umumiy xabar

Collection response pagination `meta` bilan qaytadi. Error format loyiha bo'ylab bir xil
bo'lsin. Controllerda `response()->json` status kodi noto'g'ri argumentga berilmasin.

## Audit

Quyidagilar append-only auditga tushadi:

- login bloklari va xavfli akkaunt o'zgarishlari;
- product moderatsiya va active/deactive;
- order/status/stock o'zgarishi;
- trip offer, accept, cancel, pickup va delivery;
- partial delivery qarorlari;
- seller ledger/withdrawal;
- courier cash handover va qarz;
- role/permission va setting o'zgarishi;
- admin override va reversal.

Audit actor, role, action, entity, safe old/new diff, vaqt va correlation IDni saqlaydi.

## Test talabi

Har o'zgarishda kamida:

- domain qoida uchun unit test;
- endpoint va permission uchun feature test;
- DB transaction/idempotency/concurrency uchun integration test;
- queryga ta'sir bo'lsa N+1/query-count regressiya testi;
- vaqtga bog'liq qoida bo'lsa fake clock/boundary testi;
- queue/provider bo'lsa fake va retry testi.

Ish tugashidan oldin:

```bash
php artisan test
vendor/bin/pint --test
```

Repo imkon bersa alohida MySQL test muhiti bilan ham tekshiring; SQLite lock, JSON va
index xulqini to'liq takrorlamaydi.

## Fayl joylashuvi

```text
Route       Modules/{Name}/Presentation/routes/api.php
Migration   Modules/{Name}/Infrastructure/Persistence/Migrations/
Model       Modules/{Name}/Infrastructure/Persistence/Models/
Controller  Modules/{Name}/Presentation/Controllers/
Request     Modules/{Name}/Presentation/Requests/
Resource    Modules/{Name}/Presentation/Resources/
```

Yangi feature uchun avval mavjud modul kengaytiriladi. Faqat aniq bounded context bo'lsa
yangi modul ochiladi.

## Hujjat va yakuniy hisobot

- Biznes oqimi o'zgarsa `LIFECYCLE.md` yangilanadi.
- Modul, route guruhi yoki top-level papka o'zgarsa `STRUCTURE.md` yangilanadi.
- Har faylni qo'lda sanab `STRUCTURE.md`ni shishirmang; faqat barqaror arxitektura yoziladi.
- Eski, takroriy yoki lifecyclega zid hujjat saqlanmaydi.
- Ish oxirida o'zgargan fayllar, migratsiya, endpoint, test natijasi, query ta'siri va qolgan
  xavflar qisqa aytiladi.
- Test/build muvaffaqiyatsiz bo'lsa yashirmang va "tayyor" demang.
