# Order lifecycle - texnik mapping

Asosiy biznes manba: [`../LIFECYCLE.md`](../LIFECYCLE.md). Ushbu hujjat order,
stock va statuslarni implementatsiya qilishda kerak bo'ladigan qisqa mapping.

## Asosiy obyektlar

- **Cart** - customerning vaqtinchalik savatchasi.
- **Order** - bitta sellerga tegishli xarid va moliyaviy hisob birligi.
- **Order item** - mahsulot, narx, birlik va miqdorning snapshoti.
- **Stock reservation** - active order uchun band qilingan miqdor.
- **Delivery** - bitta orderni sellerdan customerga olib borish vazifasi.
- **Trip** - bitta courier olib boradigan bir yoki ko'p delivery.

Turli seller mahsulotlari bitta orderga aralashmaydi. Ular yaqin bo'lsa faqat bitta
tripga guruhlanadi.

## Checkout

Backend checkout vaqtida har itemni qayta tekshiradi:

1. seller va product active;
2. approved product revision ishlatilmoqda;
3. current price va stock mavjud;
4. quantity minimum, maximum va stepga mos;
5. delivery address xizmat hududida;
6. delivery quote va ETA hisoblangan.

Har seller uchun alohida order yaratiladi. Narx, komissiya, tarif, manzil va item
ma'lumotlari keyingi o'zgarishlardan himoyalanish uchun snapshot qilinadi.

Checkout idempotency key bilan himoyalanadi. Ikki marta bosish duplicate order yaratmaydi.

## Operator review

Yangi order `operator_review` biznes holatiga tushadi.

- Customer olishni tasdiqlasa - order `active`.
- Customer rad etsa - `deactive/cancelled`.
- Javob bermasa - `callback_required`.
- Default uchta urinishdan keyin - `deactive/cancelled`.

Har call attempt append-only tarixda operator, vaqt, natija va izoh bilan saqlanadi.

## Stock reservation

Stock checkoutda kamaymaydi. Operator orderni active qilayotgan transactionda:

1. product qatorlari ID bo'yicha bir xil tartibda olinadi;
2. `SELECT ... FOR UPDATE`/`lockForUpdate` qilinadi;
3. har item uchun available stock qayta tekshiriladi;
4. reservation aynan bir marta yaratiladi;
5. order active bo'ladi;
6. commitdan keyin sellerga notification yuboriladi.

Yetarli stock bo'lmasa transaction to'liq rollback bo'ladi. Order yashirincha qisman
active qilinmaydi.

Bekor/deactive order reservationni idempotent ravishda aynan bir marta qaytaradi.

## Seller fulfillment

Active order sellerga ko'rinadi va default 5 soatlik tayyorlash SLA boshlanadi.

```text
active -> preparing -> ready_for_pickup
```

5 soat o'tishi orderni avtomatik bekor qilmaydi. Overdue flag, seller notification va
Dashboard alert yaratiladi.

## Delivery mapping

`ready_for_pickup` order uchun delivery route/capacity matchingga kiradi.

```text
ready_for_pickup -> assigned -> in_transit
```

Assigned faqat courier offerni qabul qilgach. In-transit faqat sellerdan yuk xavfsiz
topshirilgani tasdiqlangach.

## To'liq delivery

Courier customerga yetib borganda backend snapshotdan olinadigan naqd summani ko'rsatadi.

```text
in_transit -> delivery_confirmation -> delivered
```

`delivered` faqat quyidagilar bitta idempotent transactionda muvaffaqiyatli bo'lganda:

- assigned courier `Pulni oldim` oqimini boshlagan;
- customerning delivery PIN'i to'g'ri va muddati o'tmagan;
- cash collection yozuvi yaratilgan;
- courier liability ledger yozilgan;
- seller settlement processing eventi yaratilgan;
- audit yozilgan.

PIN noto'g'ri yoki expired bo'lsa order yopilmaydi.

## Qisman delivery

```text
in_transit -> partial_review -> delivered(partial)
```

Courier actual quantity, sabab, dalolatnoma va media dalil yuboradi. Sellerga 4 soat
beriladi. Approve bo'lsa actual quantity bo'yicha summa va ledger final qilinadi. Reject
yoki timeout bo'lsa manual Dashboard review; avtomatik approve yo'q.

## Moliyaviy vaqtlar

- PIN tasdig'idan keyin 2 soat: `processing`.
- Keyin seller balansida `pending`.
- Delivered vaqtidan 48 soat o'tgach: `withdrawable`.
- Withdrawal request summani transactionda hold qiladi.

Vaqt va foiz settinglari order/deliveryda snapshot bo'ladi.

## Status o'tish qoidasi

Status to'g'ridan controllerda o'zgartirilmaydi. Markazlashgan transition service/handler:

- current statusni tekshiradi;
- actor permission va ownershipni tekshiradi;
- invariantlarni tekshiradi;
- transaction va idempotency ishlatadi;
- status history va audit yozadi;
- notificationni commitdan keyin jo'natadi.

## Query talabi

- Order listlar pagination va qat'iy max limit bilan.
- Items, seller, delivery va kerakli counts eager loaded.
- Resource/accessorda query yo'q.
- Dashboard summary `withCount`, `withSum`, DB aggregate yoki projection bilan.
- `status + created_at`, `seller_id + status`, `customer_id + created_at` kabi real
  querylarga mos composite indexlar query plan bilan tekshiriladi.
- Collection feature testi itemlar soni oshganda N+1 bo'lmasligini tekshiradi.

## Minimal test matrix

- turli seller cartining alohida orderlarga ajralishi;
- duplicate checkout idempotency;
- parallel active qilishda stock manfiy bo'lmasligi;
- cancellation reservationni bir marta qaytarishi;
- uchta callback attempt;
- 5 soat SLA boundary;
- PINsiz delivered bo'lmasligi;
- duplicate PIN confirm ledgerni ikki marta yozmasligi;
- partial approve/reject/4h timeout;
- 2h/48h seller balance boundary;
- order listda query-count regressiyasi.
