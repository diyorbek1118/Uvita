# Uvita Dashboard API — Frontend Qo'llanma

> Staff web dashboard (super_admin / admin / manager) uchun API hujjati.
> Courier alohida mobil ilova bilan ishlaydi — bu hujjatga kirmaydi.

- **Base URL:** `http://localhost:8000/api` (dev) — barcha yo'llar `/api` bilan boshlanadi
- **Format:** JSON. Har so'rovga `Accept: application/json` yuboring.
- **Auth:** Sanctum Bearer token.

---

## 1. Autentifikatsiya

### Login — barcha rollar uchun bitta endpoint
```
POST /staff/login
Content-Type: application/json

{ "email": "super@uvita.uz", "password": "password123" }
```
**200:**
```json
{
  "data": {
    "staff": { "id": 1, "name": "Sardor SuperAdmin", "email": "super@uvita.uz", "role": "super_admin" },
    "token": "6|abcdef..."
  },
  "message": "Kirish muvaffaqiyatli"
}
```
Frontend: `token` ni saqlang, `staff.role` bo'yicha menyu/route chizing.
Keyingi barcha so'rovlarda header: `Authorization: Bearer {token}`.

**Xato:** noto'g'ri parol → `401`, faol emas → `403`.

### Logout
```
POST /staff/logout          (Authorization: Bearer {token})
→ 200 { "message": "Chiqish muvaffaqiyatli" }
```

### Test akkauntlar (seed)
| Rol | Email | Parol |
|-----|-------|-------|
| Super Admin | `super@uvita.uz` | `password123` |
| Admin | `admin@uvita.uz` | `password123` |
| Manager | `manager@uvita.uz` | `password123` |

---

## 2. Umumiy konvensiyalar

**Muvaffaqiyat (bitta resurs):**
```json
{ "data": { ... }, "message": "..." }   // message ba'zan bo'lmaydi
```

**Ro'yxat + pagination** (Laravel resource collection):
```json
{
  "data": [ { ... }, { ... } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 20, "total": 30, "last_page": 2, "from": 1, "to": 20 }
}
```

**Xato:**
```json
{ "message": "..." }                                  // 401/403/404/422/429
{ "message": "Ma'lumotlar noto'g'ri.", "errors": { "field": ["..."] } }   // 422 validation
```

**Status kodlar:** `200` OK · `201` yaratildi · `204` o'chirildi (body yo'q) ·
`401` token yo'q · `403` ruxsat yo'q · `404` topilmadi · `422` validatsiya/biznes xato.

---

## 3. Rollar va ruxsatlar

| Endpoint guruhi | Manager | Admin | Super Admin |
|-----------------|:-------:|:-----:|:-----------:|
| Mahsulot ko'rish/yaratish/tahrir | ✅ *(faqat o'ziniki)* | ✅ | ✅ |
| Mahsulot o'chirish | ❌ | ✅ | ✅ |
| Buyurtma ko'rish | ✅ *(paid+)* | ✅ | ✅ |
| Buyurtma narx breakdown (`financials`) | ❌ | ✅ | ✅ |
| Xodim ko'rish/yaratish/tahrir | ❌ | ✅ *(faqat manager/courier)* | ✅ |
| Xodim o'chirish | ❌ | ❌ | ✅ |
| Analitika (operatsion) | ❌ | ✅ | ✅ |
| Analitika (moliyaviy: sales, revenue) | ❌ | ❌ | ✅ |

> Ruxsat backend'da majburlanadi. Frontend menyuni `role` bo'yicha yashiradi (UX);
> ruxsatsiz endpoint `403` qaytaradi.

---

## 4. Mahsulotlar (`/dashboard/products`)

Manager faqat **o'z** mahsulotlarini ko'radi; admin/super hammasini.

### Ro'yxat
```
GET /dashboard/products?status=&category_id=&search=&per_page=20
```
- `status`: `active` | `inactive` | `rejected` (`pending` = `inactive` alias)
- `category_id`: butun son · `search`: nom bo'yicha · `per_page`: default 20

**Item shakli:**
```json
{
  "id": 5, "name": "Pepperoni", "slug": "pepperoni-5", "description": "...",
  "price": 45000, "stock": 70,
  "sold_count": 12, "revenue": 540000,
  "status": "active", "images": ["https://..."],
  "rating": 4.5, "reviews_count": 8, "rejection_reason": null,
  "category": { "id": 1, "name": "Pizza", "slug": "pizza" },
  "manager": { "id": 3, "name": "Abdulaziz Manager" },
  "created_at": "2026-07-11T04:29:54.000000Z",
  "updated_at": "2026-07-11T04:29:54.000000Z"
}
```
`sold_count` = sotilgan dona (paid+ buyurtmalar), `revenue` = shu mahsulotdan tushum.

### Kam qolganlar (low-stock)
```
GET /dashboard/products/low-stock?threshold=10&per_page=20
```
Ro'yxat bilan bir xil shakl, `stock <= threshold`, kamdan ko'pga saralangan.

### Bitta mahsulot
```
GET /dashboard/products/{id}          → { "data": { ...yuqoridagi shakl... } }
```
Topilmasa (yoki manager begona mahsulotni so'rasa) → `404`.

### Yaratish
```
POST /dashboard/products
{
  "name": "Yangi mahsulot",
  "description": "Tavsif",
  "price": 45000,
  "stock": 20,
  "images": ["https://picsum.photos/400/300"],
  "category_id": 1
}
→ 201 { "data": {...}, "message": "Mahsulot yaratildi" | "...moderatsiya kutilmoqda" }
```
- `slug` avtomatik (name'dan) — yuborish shart emas.
- Manager yaratsa → `status: inactive` (admin tasdig'i kerak).
- Admin/super yaratsa → `status: active`.

### Tahrirlash
```
PUT /dashboard/products/{id}
{ "name","description","price","stock","images","category_id" }  // hammasi majburiy
→ 200 { "data": {...}, "message": "Mahsulot yangilandi" }
```
Manager begona mahsulotni tahrirlasa → `403`.

### O'chirish (faqat admin/super)
```
DELETE /dashboard/products/{id}       → 204
```
Manager → `403`.

---

## 5. Buyurtmalar (`/dashboard/orders`)

Manager faqat **paid+** statusdagilarni ko'radi. `financials` bloki faqat admin/super'ga.

### Ro'yxat
```
GET /dashboard/orders?status=&search=&date_from=&date_to=&per_page=20
```
- `status`: buyurtma statusi (pastdagi ro'yxat) · `search`: telefon yoki mijoz ismi
- `date_from`, `date_to`: `YYYY-MM-DD`

**Item:**
```json
{
  "id": 12, "status": "delivered",
  "customer": { "name": "Ali Valiyev", "phone": "+998901234567" },
  "courier": { "id": 4, "name": "Bobur Courier" },
  "items_count": 3,
  "total_price": 250000, "service_fee": 37500, "grand_total": 287500,
  "delivery_time": "2026-07-12 14:00", "created_at": "2026-07-11T..."
}
```

### Bitta buyurtma (timeline + narx)
```
GET /dashboard/orders/{id}
```
```json
{
  "data": {
    "id": 12, "status": "delivered",
    "customer": { "name": "Ali", "phone": "+998...", "phone_secondary": null },
    "courier": { "id": 4, "name": "Bobur Courier" },
    "address": { "region": "Toshkent", "district": "...", "street": "...", "house": "1", "landmark": null },
    "delivery_time": "2026-07-12 14:00", "courier_note": null, "not_found_count": 0,
    "items": [
      { "product_id": 5, "product_name": "Pepperoni", "quantity": 2, "price": 45000, "subtotal": 90000 }
    ],
    "pricing": { "total_price": 250000, "service_fee": 37500, "grand_total": 287500 },
    "financials": {                        // FAQAT admin/super — manager'da bo'lmaydi
      "seller_amount": 250000, "platform_fee_gross": 37500,
      "courier_fee": 15000, "platform_fee_net": 22500, "customer_total": 287500
    },
    "timeline": {
      "created_at": "2026-07-11T04:29:54.000000Z",
      "paid_at": "2026-07-11T04:39:54.000000Z",
      "confirmed_at": "2026-07-11T05:29:54.000000Z",
      "ready_at": "...", "delivering_at": "...", "delivered_at": "...",
      "delivery_issue_at": null, "cancelled_at": null
    },
    "created_at": "2026-07-11T04:29:54.000000Z"
  }
}
```
`timeline` da `null` = shu bosqichga hali yetmagan. Grafik/qadamlar uchun ishlating.

> ⚠️ **Narx:** mijoz **mahsulot + 15% xizmat haqi** to'laydi (`service_fee`). Yetkazish **tekin**.
> `courier_fee` — platformaning ichki xarajati, **mijozga hech qachon ko'rsatilmaydi**
> (faqat admin/super `financials` blokida).

---

## 6. Xodimlar (`/dashboard/staff`) — admin + super

Admin faqat **manager/courier** bilan ishlaydi (admin/super rolini yarata/tahrirlay olmaydi).
Manager bu bo'limga umuman kira olmaydi (`403`).

```
GET    /dashboard/staff?role=manager           // ro'yxat (role — ixtiyoriy filtr)
GET    /dashboard/staff/{id}                    // bitta
POST   /dashboard/staff                         // yaratish
PUT    /dashboard/staff/{id}                    // tahrir
PUT    /dashboard/staff/{id}/toggle-active      // faol/blok
```
**StaffResource:** `{ "id","name","email","role","is_active","created_at" }`

**Yaratish body:** `{ "name","email","password","role" }` — `role`: `manager|courier|admin|super_admin`
(admin `admin|super_admin` tanlasa → `403`).

**Tahrir body:** `{ "name","email","role" }`

### O'chirish — faqat super admin
```
DELETE /super/staff/{id}      → 204
```

---

## 7. Analitika (`/dashboard/analytics`)

### Operatsion (admin + super)

**Summary — bosh sahifa KPI:**
```
GET /dashboard/analytics/summary
```
```json
{ "data": {
  "orders": { "today": 3, "this_month": 25, "total": 25 },
  "pending_approvals": 3, "delivery_issues": 1, "active_couriers": 2,
  "low_stock": 4, "active_products": 26, "total_customers": 20
} }
```

**Buyurtma status funnel:**
```
GET /dashboard/analytics/order-status
→ { "data": { "pending":2,"paid":3,"confirmed":1,"ready_to_deliver":1,
              "delivering":2,"delivered":10,"delivery_issue":1,"cancelled":5,"total":25 } }
```

**Eng ko'p sotilgan mahsulotlar:**
```
GET /dashboard/analytics/top-products?limit=10
→ { "data": [ { "id":5,"name":"Pepperoni","units_sold":42,"revenue":1890000 } ] }
```

### Moliyaviy (faqat super admin)

**Sotuv vaqt qatori (grafik):**
```
GET /dashboard/analytics/sales?period=daily|weekly|monthly&from=YYYY-MM-DD&to=YYYY-MM-DD
```
```json
{ "data": [
  { "period": "2026-07-11", "orders_count": 4, "gross_sales": 3708479,
    "courier_fees": 210000, "platform_fee_net": 346272, "customer_total": 4264751 }
] }
```
- `period` formati: daily → `2026-07-11`, weekly → `2026-W28`, monthly → `2026-07`.

**Umumiy tushum taqsimoti:**
```
GET /dashboard/analytics/revenue?from=&to=
→ { "data": {
     "orders_count": 14, "gross_sales": 3708479, "seller_payouts": 3708479,
     "platform_fee_gross": 556272, "courier_fees": 210000,
     "platform_fee_net": 346272, "customer_total": 4264751
} }
```

---

## 8. Narx modeli (frontend uchun muhim)

```
mijoz_to'lovi (grand_total) = mahsulotlar (total_price) + 15% xizmat haqi (service_fee)
```
- **Yetkazish mijozdan olinmaydi** (tekin).
- **Kuryer haqi** (`courier_fee`) — pog'onali, platformaning ichki xarajati.
  Mijozga **KO'RINMAYDI**. Faqat admin/super buyurtma detalidagi `financials` blokida ko'radi.
- Kuryer tarifi (ichki): `<200k → 10k`, `200–300k → 15k`, `≥300k → 20k`.
- Minimal buyurtma **50 000 so'm** (mijoz checkout'da) — kam bo'lsa `422`.

---

## 9. Ma'lumotnoma (enums)

**Buyurtma statuslari:**
`pending` → `paid` → `confirmed` → `ready_to_deliver` → `delivering` → `delivered`
· `cancelled` · `delivery_issue`

**Mahsulot statuslari:** `active` · `inactive` (moderatsiyada) · `rejected`

**Rollar:** `super_admin` · `admin` · `manager` · `courier`

**To'lov provayderlari:** `payme` · `click` · `uzum`
