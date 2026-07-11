# Uvita — To'liq Buyurtma Life Cycle (v2)

> Ushbu hujjat platformadagi barcha ishtirokchilarning harakatlari,
> tizim ichidagi oqimlar, status o'zgarishlari va ruxsatlar tizimini
> to'liq va aniq tasvirlaydi.
>
> **Versiya:** 2.0

---

## Ishtirokchilar

| Rol | Kim | Kirish usuli |
|-----|-----|-------------|
| **Customer** | Xaridor | OTP (telefon) |
| **Manager** | Do'kon xodimi | Email + parol |
| **Courier** | Yetkazuvchi | Email + parol |
| **Admin** | Tizim boshqaruvchisi | Email + parol |
| **Super Admin** | Tizim egasi / platforma rahbari | Email + parol |

---

## Buyurtma Statuslari

```
pending          → Buyurtma yaratildi, to'lov kutilmoqda
paid             → To'lov webhook tasdiqladi
confirmed        → Manager qabul qildi, yig'ilmoqda
ready_to_deliver → Manager yig'di va kuryerga topshirdi
delivering       → Kuryer qabul qildi, yetkazilmoqda
delivered        → Buyurtma yetkazildi
cancelled        → Bekor qilindi
delivery_issue   → Kuryer 3 marta topa olmadi, admin hal qilmoqda
```

### Status O'zgarish Diagrammasi

```
pending ──────────────────────────────────────────── cancelled
   │                                                  (customer, to'lovsiz)
   │ [to'lov webhook keladi]
   ▼
paid
   │
   │ [manager buyurtmani qabul qiladi]
   ▼
confirmed
   │
   │ [manager yig'adi va "Kuryerga topshirish" bosadi]
   ▼
ready_to_deliver ◄── [kuryer rad etsa → admin boshqa kuryer tayinlaydi]
   │
   │ [admin kuryer tayinlaydi → kuryer qabul qiladi]
   ▼
delivering
   │                    ┌─── 1-chi yoki 2-chi "Topilmadi"
   │ ◄──────────────────┘    (status o'zgarmaydi, vaqt yangilanadi)
   │
   │ [3-chi marta "Topilmadi"]
   ▼
delivery_issue
   │
   ├──► Admin mijoz bilan bog'lanadi:
   │         ├──► Yangi vaqt kelishiladi → delivering (yana uriniladi)
   │         └──► Bekor + refund (agar mijoz xohlasa) → cancelled
   │
   │ [Kuryer muvaffaqiyatli topshiradi]
   ▼
delivered
   │
   │ [24 soat o'tgach — avtomatik]
   ▼
[Review so'rovi yuboriladi]
```

---

## Rollar va Ruxsatlar

### Umumiy Ruxsatlar Jadvali

| Amal | Customer | Manager | Courier | Admin | Super Admin |
|------|----------|---------|---------|-------|-------------|
| Mahsulotlarni ko'rish (faol) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Mahsulot yaratish | ❌ | ✅ *(inactive → approval kerak)* | ❌ | ✅ *(active — to'g'ridan)* | ✅ *(active — to'g'ridan)* |
| Mahsulot tasdiqlash → active | ❌ | ❌ | ❌ | ✅ | ✅ |
| Mahsulot tahrirlash | ❌ | ✅ *(o'ziniki)* | ❌ | ✅ | ✅ |
| Mahsulot o'chirish | ❌ | ❌ | ❌ | ✅ | ✅ |
| Buyurtma berish | ✅ | ❌ | ❌ | ❌ | ✅ |
| Barcha buyurtmalarni ko'rish | ❌ | ✅ *(paid/confirmed)* | ❌ | ✅ | ✅ |
| Buyurtmani qabul qilish (confirmed) | ❌ | ✅ | ❌ | ❌ | ✅ |
| Buyurtmani yig'ish (ready_to_deliver) | ❌ | ✅ | ❌ | ❌ | ✅ |
| Kuryerni tayinlash | ❌ | ❌ | ❌ | ✅ | ✅ |
| Buyurtmani qabul qilish (delivering) | ❌ | ❌ | ✅ | ❌ | ✅ |
| "Yetkazildi" bosish | ❌ | ❌ | ✅ | ❌ | ✅ |
| "Topilmadi" bosish | ❌ | ❌ | ✅ | ❌ | ✅ |
| delivery_issue hal qilish | ❌ | ❌ | ❌ | ✅ | ✅ |
| Buyurtmani bekor qilish | ✅ *(pending)* | ❌ | ❌ | ❌ | ✅ |
| Foydalanuvchi asosiy ma'lumotlari | ❌ | ❌ | ❌ | ✅ *(cheklangan)* | ✅ |
| Foydalanuvchi to'liq ma'lumotlari | ❌ | ❌ | ❌ | ❌ | ✅ |
| Tranzaksiyalarni ko'rish | ❌ | ❌ | ❌ | ❌ | ✅ |
| Sharh moderatsiya | ❌ | ❌ | ❌ | ✅ | ✅ |
| Admin yaratish | ❌ | ❌ | ❌ | ❌ | ✅ |
| Manager / Courier yaratish | ❌ | ❌ | ❌ | ✅ | ✅ |
| Tizim sozlamalari (narx, hudud) | ❌ | ❌ | ❌ | ❌ | ✅ |

---

### Admin (Oddiy) — Ko'ra OLMAYDI:

```
❌ Foydalanuvchilarning shaxsiy profil tarixi
❌ To'lov tranzaksiyalari va moliyaviy ma'lumotlar
❌ Boshqa adminlar ma'lumotlari
✅ Buyurtmalar (faqat manzil + ism + telefon — yetkazish uchun zarur)
✅ Mahsulot yaratish (yaratilishi bilanoq active — approval shart emas)
✅ Mahsulotlarni tasdiqlash / rad etish (manager yaratganlarni)
✅ Sharhlarni moderatsiya qilish
✅ Kuryer tayinlash va boshqarish
```

### Super Admin — Hamma narsani qila oladi:

```
✅ Customer kabi: buyurtma bera oladi, to'lay oladi
✅ Manager kabi: buyurtmani qabul qiladi, yig'adi, kuryerga topshiradi
✅ Courier kabi: buyurtmani delivering ga o'tkazadi, yetkazildi bosadi
✅ Admin kabi: kuryer tayinlaydi, mahsulot tasdiqlaydi, sharhlarni moderatsiya qiladi
✅ + Tranzaksiyalar, to'liq user ma'lumotlari, tizim sozlamalari
```

### Manager — Ko'ra OLMAYDI va Qila OLMAYDI:

```
❌ Buyurtmani bekor qilish
❌ Refund amalga oshirish
❌ Customer to'liq profili va to'lov tarixi
❌ Boshqa managerlarga tegishli mahsulotlar (CRUD)
✅ Faqat paid statusdagi buyurtmalarni olish
✅ Buyurtmani yig'ish va kuryerga topshirish
✅ O'z mahsulotlarini yaratish (admin tasdiqlagunicha inactive)
```

---

## Mahsulot Approval Tizimi

**Admin / Super Admin mahsulot yaratsa:**
```
1. Admin yoki Super Admin yangi mahsulot yaratadi
   → status: active (darhol)
   → Mahsulot saytda ko'rinadi va sotilishi mumkin
```

**Manager mahsulot yaratsa:**
```
1. Manager yangi mahsulot yaratadi
   → status: inactive (pending_review)
   → Faqat manager o'zi va adminlar ko'radi

2. Admin / Super Admin yangi mahsulotlar ro'yxatini ko'radi
   → Ko'rib chiqadi: nomi, narxi, tavsifi, rasmlari, kategoriyasi

3. Tasdiqlaydi → status: active
   → Mahsulot saytda ko'rinadi va sotilishi mumkin

   Rad etadi → status: rejected
   → Manager ga Notification: "Mahsulot rad etildi. Sabab: [...]"

4. Manager rejected mahsulotni tahrirlaydi
   → Qayta pending_review ga o'tkazadi
   → Admin yana ko'rib chiqadi
```

> **Qoida:** Manager yaratgan mahsulot admin yoki super admin tasdiqisiz
> saytda ko'rinmaydi. Admin/Super Admin yaratgan mahsulotlar approval talab qilmaydi.

---

## Mahsulot Stock Tizimi

### Ko'rinish Qoidasi

```
stock > 0  → Mahsulot ko'rinadi, sotib olish mumkin
stock = 0  → "Tugagan" belgisi bilan ko'rinadi, sotib bo'lmaydi
```

### Stock O'zgarish Nuqtalari

```
Buyurtma yaratishda     → SELECT FOR UPDATE + stock tekshiriladi
                           (yetarli emas → 422 xato)
To'lov webhook kelganda → Idempotency tekshiriladi (transaction_id)
                           → Stock atomik kamayadi (decrement)
Buyurtma cancelled      → Stock qaytarilmaydi
                           (pending holatda stock hali kamaygan emas)
```

### Race Condition Yechimi — SELECT FOR UPDATE

```php
// OrderService — buyurtma yaratish
DB::transaction(function () use ($cartItems) {
    foreach ($cartItems as $item) {
        // Bir vaqtda ikki buyurtma kelsa — biri kutadi
        $product = Product::lockForUpdate()->find($item['product_id']);

        if ($product->stock < $item['quantity']) {
            throw new InsufficientStockException($product->name);
            // → 422: "Mahsulot yetarli emas: [mahsulot nomi]"
        }
        // Order yaratiladi — stock HALI kamaytirilmaydi
        // Stock faqat to'lov webhook tasdiqlanganda kamayadi
    }
    // Order + OrderItems + Payment (pending) yaratiladi
});
```

### Webhook Idempotency — transaction_id

```php
// PaymentWebhookHandler
$alreadyProcessed = Payment::where('transaction_id', $webhookData['transaction_id'])
                           ->where('status', 'paid')
                           ->exists();

if ($alreadyProcessed) {
    return response()->json(['status' => 'ok']); // Duplicate → skip, 200 qaytariladi
}

// Yangi to'lov → atomik davom ettiriladi
DB::transaction(function () use ($order, $webhookData) {
    $payment->update([
        'status'         => 'paid',
        'transaction_id' => $webhookData['transaction_id'],
    ]);
    $order->update(['status' => 'paid']);

    foreach ($order->items as $item) {
        $item->product->decrement('stock', $item['quantity']);
        // Stock kamayishi faqat shu yerda — bir marta
    }
});
```

---

## Narx va Yetkazish Tizimi

```
Hudud:          Bitta shahar (Super Admin sozlaydi)
Yetkazish:      Mijozdan yetkazish uchun pul OLINMAYDI (tekin)
Xizmat haqi:    Mahsulotlar summasining 15% — mijoz shuni qo'shib to'laydi
Minimal summa:  50 000 so'm — kam bo'lsa buyurtma RAD etiladi (422)
Kuryer haqi:    Pog'onali; platforma o'z 15% ustamasidan to'laydi — MIJOZGA KO'RINMAYDI
                  < 200 000  → 10 000
                  200–300k   → 15 000
                  ≥ 300 000  → 20 000
```

**Mijoz to'lovi = Mahsulotlar summasi + 15% xizmat haqi**  (yetkazish tekin)

Ichki taqsimot:
```
Sotuvchi oladi:      mahsulotlar summasi           (total_price)
Platforma ustamasi:  15% xizmat haqi               (service_fee)  ← mijoz to'laydi
Kuryerga:            pog'onali haq                 (courier_fee)  ← 15% ustamadan, ichki
Platformada qoladi:  service_fee − courier_fee
```

Narxlar buyurtma yaratilganda **snapshotga** olinadi (total_price, service_fee,
courier_fee, grand_total). Keyin qoidalar o'zgarsa — eski buyurtmalarga ta'sir qilmaydi.

> Eslatma: 15% hozircha alohida "xizmat haqi" satri bo'lib ko'rsatiladi.
> Kelajakda mahsulot narxiga qo'shib ko'rsatilishi mumkin.

---

## To'liq Life Cycle

---

### BOSQICH 1: Mahsulotlarni Ko'rish (Login shart emas)

```
1. Customer ilovani ochadi — loginsiz
2. Kategoriyalar bo'yicha ko'radi
3. Mahsulotlarni filter qiladi (kategoriya / narx oralig'i / qidiruv)
4. Mahsulot detail sahifasini ochadi
5. Narx, tavsif, rasmlar, sharhlar va yulduzchalarni ko'radi
6. stock = 0 bo'lsa → "Tugagan" ko'rinadi, buyurtma tugmasi o'chirilgan
```

> Savatchaga qo'shish yoki buyurtma berishga harakat qilsa →
> avtomatik OTP login oqimi boshlanadi.

---

### BOSQICH 2: Customer Kirishi (OTP)

```
1. "Savatchaga qo'sh" yoki "Buyurtma berish" tugmasini bosadi
2. Telefon raqam kiritish sahifasi chiqadi (+998XXXXXXXXX)
3. OTP SMS yuboriladi (120 soniya amal qiladi)
4. OTP kodni kiritadi
5. Yangi foydalanuvchi bo'lsa → ism kiritish so'raladi:
   - Ism *
   - Telefon (avtomatik — kirish paytida to'ldirilgan)
6. Sanctum token oladi (muddati: 30 kun)
7. Asl harakati (savatcha / buyurtma) davom ettiradi
```

**OTP Xavfsizlik Qoidalari:**

```
Amal muddati:           120 soniya
Maksimal urinish:       5 ta (noto'g'ri kod kiritish)
5 marta noto'g'ri:      10 daqiqa blok (IP + telefon raqam bo'yicha)
Yangi OTP so'rash:      Avvalgi OTP muddati tugaganidan keyin mumkin
Rate limiting:          IP va telefon raqam bo'yicha alohida kuzatiladi
```

---

### BOSQICH 3: Savatcha

```
1. Customer mahsulot sahifasida "Savatchaga qo'sh" bosadi
2. Miqdorni tanlaydi
3. Savatcha yangilanadi (DB da saqlanadi)
4. Savatcha sahifasida ko'rinadi:
   - Mahsulotlar ro'yxati + miqdor
   - Mahsulotlar jami narxi
   - Xizmat haqi (15%)
   - To'lash kerak bo'lgan jami summa (yetkazish tekin)
```

**Qoidalar:**

```
- stock = 0 → savatchaga qo'shib bo'lmaydi
- Bir xil mahsulotni qayta qo'shsa → miqdor oshadi
- Mavjud stockdan ortiq miqdor kiritib bo'lmaydi
- Savatcha DB da saqlanadi (sessiya tugasa ham yo'qolmaydi)
```

---

### BOSQICH 4: Buyurtma Berish

```
1. Customer savatchani ko'radi
2. "Buyurtma berish" tugmasini bosadi
3. Yetkazish ma'lumotlarini kiritadi:
   - To'liq manzil * (viloyat, tuman, ko'cha, uy raqami)
   - Mo'ljal — ixtiyoriy
   - Asosiy telefon * (profildan avtomatik, o'zgartirsa bo'ladi)
   - Qo'shimcha telefon — ixtiyoriy
   - Yetkazish vaqti * (kun va soat oralig'i)
     Masalan: "Ertaga 14:00–18:00"
   - Kuryer uchun izoh — ixtiyoriy
4. To'lov usulini tanlaydi: Payme / Click / Uzum
5. Jami summa ko'rinadi: mahsulotlar + 15% xizmat haqi (yetkazish tekin)
6. "To'lov qilish" tugmasini bosadi
```

**Tizim ichida — buyurtma yaratish:**

```
1. DB transaction ochiladi
2. Har bir mahsulot uchun: SELECT FOR UPDATE + stock tekshiriladi
   → stock < quantity → 422: "Mahsulot yetarli emas: [nomi]"
   → Xato chiqsa — savatcha o'zgarmaydi, customer xabar oladi
3. Minimal buyurtma tekshiriladi: mahsulotlar < 50 000 → 422 (buyurtma yaratilmaydi)
4. Order yaratiladi (status: pending)
5. Order items yaratiladi:
   → mahsulot_id + miqdor + shu paytdagi narx (snapshot)
6. Narx snapshot: service_fee (15%) va courier_fee (pog'onali, ichki) hisoblanadi
6. Savatcha tozalanadi
7. Payment yaratiladi (status: pending)
8. Customer ga SMS: "Buyurtmangiz #123 yaratildi. To'lovni amalga oshiring."
9. To'lov provider URL qaytariladi
10. Customer provider sahifasiga yo'naltiriladi
```

---

### BOSQICH 5: To'lov

```
1. Customer Payme / Click / Uzum sahifasida to'laydi
2. Provider webhook yuboradi
3. Tizim webhook ni qabul qiladi:
   a. Signature tekshiriladi (provider kaliti bilan)
   b. transaction_id avval kelganmi? → Ha → skip, 200 OK qaytariladi
   c. DB transaction:
      - Payment: pending → paid (transaction_id yoziladi)
      - Order:   pending → paid
      - Har bir mahsulot: stock atomik kamayadi (decrement)
   d. Customer ga SMS: "To'lovingiz qabul qilindi. Buyurtma #123"
   e. Manager ga Telegram: "Yangi to'langan buyurtma #123 keldi!"
```

**To'lov muvaffaqiyatsiz bo'lsa:**

```
- Payment status: failed
- Order status:   pending (o'zgarmaydi)
- Stock:          o'zgarmaydi
- Customer ga SMS: "To'lov amalga oshmadi. Qayta urinib ko'ring."
- Buyurtma sahifasida "Qayta to'lash" tugmasi chiqadi:
  → Yangi payment yaratiladi
  → Yangi provider URL beriladi
  → Eski failed payment tarix uchun saqlanib qoladi
```

**To'lovdan oldin bekor qilish (pending holatda):**

```
- Customer o'zi bekor qiladi
- Order: pending → cancelled
- Stock qaytarilmaydi (chunki hali kamaygan emas)
- Payment: pending/failed → cancelled
```

**To'lovdan keyin pul qaytarish:**

```
Status paid va undan keyin:
  → Customer tomonidan bekor qilish YO'Q
  → Pul qaytarish YO'Q (hozircha)
  → Istisno: delivery_issue holati (9.1-bosqichga qarang)
  → Kelajakda refund tizimi qo'shilishi mumkin
```

---

### BOSQICH 6: Manager — Buyurtmani Qabul Qilish

```
1. Manager platformaga kiradi (email + parol)
2. Yangi buyurtmalar ro'yxatini ko'radi (status: paid)
3. Buyurtma detailini ko'radi:
   - Mahsulotlar ro'yxati + miqdorlar
   - Yetkazish manzili, vaqti, kuryer izohi
   - Jami summa + xizmat haqi (15%)
   - Customer: faqat ism + asosiy telefon + qo'shimcha telefon
4. Buyurtmani qabul qiladi → status: paid → confirmed
5. Customer ga SMS: "Buyurtmangiz #123 qabul qilindi. Tayyorlanmoqda."
```

> **Eslatma:** Manager faqat buyurtmani qabul qiladi va yig'adi.
> Bekor qilish va refund uning vakolati emas.

---

### BOSQICH 7: Manager — Yig'ish va Kuryerga Topshirish

```
1. Manager mahsulotlarni yig'adi va paketlaydi
2. Kuryer uchun izoh qoldirishi mumkin (ixtiyoriy)
   Masalan: "Mo'rtga ehtiyotkorlik bilan"
3. "Kuryerga topshirish" tugmasini bosadi
   → status: confirmed → ready_to_deliver
4. Admin ga Notification: "Buyurtma #123 yig'ildi — kuryer tayinlanishi kerak"
```

---

### BOSQICH 7.5: Admin — Kuryerni Tayinlash

```
1. Admin ready_to_deliver buyurtmalar ro'yxatini ko'radi
2. Bo'sh kuryerlar ro'yxatini ko'radi
3. Mos kuryerni tanlaydi va tayinlaydi
4. Kuryer ga Push Notification: "Sizga #123 buyurtma tayinlandi!"
```

> Kuryer tayinlanmasa — buyurtma ready_to_deliver da qoladi.
> Admin qayta tayinlashi mumkin.

---

### BOSQICH 8: Kuryer — Qabul Qilish

```
1. Kuryer platformaga kiradi (email + parol)
2. O'ziga tayinlangan buyurtmani ko'radi (status: ready_to_deliver)
3. Buyurtma detailini ko'radi:
   - Yetkazish manzili va vaqti
   - Customer asosiy va qo'shimcha telefoni
   - Manager uchun izoh
4. Do'kondan buyurtmani oladi
5. "Qabul qilish" tugmasini bosadi
   → status: ready_to_deliver → delivering
6. Yo'lga chiqishdan AVVAL customer ga qo'ng'iroq qiladi:
   - Manzilni aniqlashtiradi
   - Kelish vaqtini kelishadi
7. Customer ga SMS: "Kuryer yo'lda, tez orada yetkazadi!"
```

---

### BOSQICH 9: Kuryer — Yetkazish

```
1. Kuryer manzilga boradi
2. Buyurtmani topshiradi
3. "Yetkazildi" tugmasini bosadi
   → status: delivering → delivered
4. Customer ga SMS: "Buyurtmangiz #123 yetkazildi! Iltimos baholang."
5. Kuryerning yetkazish tarixi yangilanadi
```

---

### BOSQICH 9.1: Kuryer — "Topilmadi" Oqimi

**1-chi va 2-chi marta:**

```
1. Kuryer customer bilan bog'lana olmaydi yoki topib bo'lmaydi
2. "Topilmadi" tugmasini bosadi
3. Sabab kiritadi: "Telefonga chiqmadi" / "Manzil noto'g'ri" / boshqa
4. Tizim: not_found_count + 1 (1 yoki 2 bo'ladi)
5. Status o'zgarmaydi (delivering qoladi)
6. Customer ga SMS:
   "Kuryer siz bilan bog'lana olmadi.
    Iltimos telefonga chiqing yoki qayta murojaat qiling."
7. Admin ga Notification: "Buyurtma #123 — topilmadi (urinish #N)"
8. Yetkazish vaqti yangilanadi (admin qayta belgilaydi)
9. Kuryer keyingi belgilangan vaqtda yana urinadi
```

**3-chi marta (delivery_issue):**

```
1. Kuryer yana "Topilmadi" bosadi → not_found_count = 3
2. → status: delivering → delivery_issue
3. Admin ga yuqori prioritetli Notification:
   "⚠️ Buyurtma #123 — 3 marta topilmadi! Aralashish kerak."
4. Customer ga SMS:
   "Yetkazishda muammo yuzaga keldi.
    Administrator siz bilan tez orada bog'lanadi."
5. Admin mijoz bilan bog'lanadi va holat aniqlanadi:

   Variant A — Yangi vaqt kelishiladi:
   → status: delivery_issue → delivering
   → Yana kuryer tayinlanadi (xuddi shu yoki boshqa)
   → Jarayon davom etadi

   Variant B — Mijoz buyurtmani bekor qilishni xohlaydi:
   → status: delivery_issue → cancelled
   → Refund masalasi admin ixtiyori bilan hal qilinadi
   → ⚠️ Bu holat noyob — protsedura keyinroq batafsil aniqlanadi

Eslatma: delivery_issue holatlari juda kam bo'ladi.
```

---

### BOSQICH 10: Review

```
1. delivered bo'lganidan 24 soat o'tgach — avtomatik SMS yuboriladi
   YOKI customer ixtiyoriy ravishda buyurtma tarixidan baholaydi
2. Customer ga SMS: "Buyurtmangiz #123 ni baholang!"
3. Customer sharh yozadi:
   - Yulduzcha (1–5) *
   - Izoh matni — ixtiyoriy
4. Sharh darhol ko'rinmaydi → moderation queue ga tushadi
5. Admin / Super Admin sharhni ko'rib chiqadi:
   - Tasdiqlaydi → mahsulot sahifasida ko'rinadi
   - Rad etadi → customer xabar olishi mumkin (ixtiyoriy)
6. Bir buyurtma = bir sharh imkoniyati
7. Yozilgan sharh o'zgartirish mumkin (admin qayta moderatsiya qiladi)
8. Sharh o'chirilmaydi (faqat admin yashirishi mumkin)
```

---

### BOSQICH 11: Kuryer Hisob-Kitobi

> ⚠️ Bu qism keyinroq aniqlanadi

```
Har bir delivered buyurtma uchun kuryerga belgilangan haq qo'shiladi.
Hisob-kitob haftalik yoki oylik amalga oshiriladi.
Super Admin tomonidan boshqariladi.
```

---

## Bekor Qilish Qoidalari

```
┌─────────────────────────────────────────────────────────────┐
│  STATUS       │  KIM BEKOR QILA OLADI  │  STOCK    │  REFUND │
├───────────────┼────────────────────────┼───────────┼─────────┤
│  pending      │  Customer (o'zi)       │  yo'q     │  yo'q   │
│  paid+        │  Hech kim              │  —        │  yo'q   │
│  delivery_    │  Admin (ixtiyori bilan │  —        │  TBD    │
│  issue        │  mijoz roziligida)     │           │         │
└─────────────────────────────────────────────────────────────┘

pending — to'lov amalga oshmagan, stock hali kamaygan emas
paid va undan keyin — manager bekor qila olmaydi, refund yo'q (hozircha)
delivery_issue — noyob holat, admin hal qiladi
```

---

## Notification Jadvali

| Hodisa | Customer | Manager | Admin | Kuryer |
|--------|----------|---------|-------|--------|
| Buyurtma yaratildi (pending) | SMS ✅ | Telegram ✅ | — | — |
| To'lov tasdiqlandi (paid) | SMS ✅ | Telegram ✅ | — | — |
| To'lov muvaffaqiyatsiz | SMS ✅ | — | — | — |
| Buyurtma qabul qilindi (confirmed) | SMS ✅ | — | — | — |
| Yig'ildi (ready_to_deliver) | — | — | Notification ✅ | — |
| Kuryer tayinlandi | — | — | — | Push ✅ |
| Kuryer yo'lda (delivering) | SMS ✅ | — | — | — |
| Yetkazildi (delivered) | SMS ✅ | — | — | — |
| Topilmadi — 1/2 marta | SMS ✅ | — | Notification ✅ | — |
| Topilmadi — 3 marta (delivery_issue) | SMS ✅ | — | Push ✅ *(urgent)* | — |
| Review so'rovi | SMS ✅ | — | — | — |
| Mahsulot tasdiqlandi | — | Notification ✅ | — | — |
| Mahsulot rad etildi | — | Notification ✅ | — | — |

---

## Kerakli Modullar

| Modul | Guard | Tavsif |
|-------|-------|--------|
| `Auth` | — | Customer OTP auth (rate limiting, 10 daqiqa blok) |
| `User` | `api` | Customer profil, buyurtma tarixi |
| `Category` | `api` / `admin` | Kategoriyalar CRUD |
| `Product` | `api` / `admin` | Mahsulotlar CRUD + approval oqimi + filter |
| `Cart` | `api` | Savatcha (DB da, persistent) |
| `Order` | `api` / `admin` | Buyurtma oqimi + status boshqarish |
| `Payment` | — | To'lov + webhook + idempotency (transaction_id) |
| `Review` | `api` / `admin` | Sharh, baholash, moderatsiya queue |
| `Courier` | `courier` | Kuryer auth + buyurtma qabul + yetkazish + topilmadi |
| `Admin/Auth` | `admin` | Manager, Admin, Super Admin login |
| `Admin/Order` | `admin` | Buyurtmalarni ko'rish, boshqarish, delivery_issue |
| `Admin/Product` | `admin` | Mahsulot approval / reject |
| `Admin/Courier` | `admin` | Kuryer tayinlash, boshqarish |
| `Admin/Review` | `admin` | Sharh moderatsiyasi |
| `Admin/User` | `super_admin` | To'liq user ma'lumotlari |
| `Admin/Transaction` | `super_admin` | To'lov tranzaksiyalari tarixi |
| `Admin/Settings` | `super_admin` | Xizmat haqi, shahar, minimal buyurtma, tizim parametrlari |
| `Notification` | — | SMS + Telegram + Push (Shared Service) |

---

## Tizim Chegaralari (Hozirgi Versiya v2)

```
Yetkazish hududi:     Bitta shahar (Super Admin tomonidan belgilanadi)
Yetkazish narxi:      Mijozdan OLINMAYDI (tekin); mijoz 15% xizmat haqi to'laydi
Kuryer haqi:          Pog'onali, platforma 15% ustamadan to'laydi — mijozga ko'rinmaydi
Minimal buyurtma:     50 000 so'm — kam bo'lsa buyurtma rad etiladi (422)
Pul qaytarish:        Faqat pending holatda (to'lovsiz bekor qilish)
                      Delivery_issue — admin ixtiyori bilan (TBD)
Kuryer "Topilmadi":   3 marta → delivery_issue → admin hal qiladi
OTP blok:             5 noto'g'ri urinish → 10 daqiqa blok
Sharh moderatsiya:    Admin / Super Admin tomonidan
Mahsulot tasdiqlash:  Admin / Super Admin tomonidan
Kuryer hisob-kitobi:  Keyinroq aniqlanadi
```

---

> **Eslatma:**
> Bu hujjat loyiha rivojlanishi bilan yangilanadi.
> Har qanday oqim o'zgarishi avval shu yerda muhokama qilinadi.
> v2 — barcha muhokamalar va yechimlar kiritilgan versiya.
