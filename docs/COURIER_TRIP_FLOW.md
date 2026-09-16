# Courier trip lifecycle - texnik mapping

Asosiy biznes manba: [`../LIFECYCLE.md`](../LIFECYCLE.md). Ushbu hujjat courier
offer, trip, pickup, delivery va naqd pul oqimini aniqlashtiradi.

## Courier eligibility

Courierga offer chiqishi uchun:

- akkaunt `active`;
- courier `online`;
- tanlangan vehicle active;
- vehicle og'irlik, hajm, masofa va maxsus yuk talabiga mos;
- courier yangi trip olish bo'yicha cash-debt blokida emas;
- bir vaqtdagi active trip qoidasi buzilmaydi.

`is_active`, `is_online` va `cash_debt_blocked` bitta boolean sifatida aralashtirilmaydi.

## Matching

Matching faqat masofa yaqinligiga qaramaydi:

- active route va schedule;
- origin/destination hududi;
- route corridor va maksimal detour;
- pickup/drop ketma-ketligi;
- jami og'irlik va qolgan capacity;
- jami fizik hajm;
- courier maksimal masofasi;
- mahsulotlarning birga tashishga mosligi;
- maxsus tashish sharti;
- ETA va vaqt oynasi.

Mos kelmagan delivery uchun rad sababi saqlanadi. Og'ir geospatial hisob provider
interface ortida va aniq invalidationli cache bilan ishlashi mumkin.

## Offer

Courier quyidagilarni ko'radi:

- yo'nalish;
- pickup va drop-off soni;
- jami og'irlik/hajm;
- taxminiy masofa/vaqt;
- customerlardan olinadigan naqd summa;
- taxminiy courier daromadi.

Accept transactionida eligibility va capacity qayta tekshiriladi. Ikki courier bir
deliveryni parallel accept qila olmaydi.

## Bekor qilish

Courier offer/tripni accept qilgandan keyin default 1 soat ichida, pickup hali
tasdiqlanmagan bo'lsa sabab bilan bekor qilishi mumkin.

- Bir soatdan keyin oddiy cancel yo'q.
- Pickupdan keyin oddiy cancel yo'q.
- Oldin rad qilingan aynan shu taklif oddiy qayta so'rovda shu courierga berilmaydi.
- Istisno faqat Logistics Manager override, sabab va audit bilan.

## Pickup

Har seller pickupida courier:

1. order item va kutilgan miqdorni ko'radi;
2. miqdor, sifat/brak va qadoqni tekshiradi;
3. maxsus tashish shartini qabul qiladi;
4. kerakli foto, vaqt va GPS dalilini beradi;
5. seller bilan xavfsiz handover tasdig'ini bajaradi.

Courierning o'zi seller tomon tasdig'ini soxtalashtira olmaydi. GPS bo'lmasa faqat
permissionli override va sabab bilan davom etiladi.

Pickup tasdiqlangach tashish davridagi kamomad, buzilish yoki yo'qotish javobgarligi
courierga o'tadi. Uvita omboriga topshirish bosqichi yo'q.

## In-transit va qo'shimcha delivery

Trip davomida yo'ldagi qo'shimcha order faqat:

- qolgan weight/volume capacityga sig'sa;
- detour va ETA limitini buzmasa;
- mahsulot mosligi saqlansa;
- courier qabul qilsa

tripga qo'shiladi. Har order status va hisob-kitobda mustaqil qoladi.

GPS faqat active trip/delivery davomida, minimal kerakli chastotada olinadi.

## Full delivery

1. Courier `Topshirish`ni boshlaydi.
2. Backend olinadigan aniq cash summani ko'rsatadi.
3. Courier `Pulni oldim`ni bosadi.
4. Customerga delivery PIN yuboriladi.
5. To'g'ri PIN idempotent transactionda deliveryni yopadi.
6. Courier cash liability va earning ledger yoziladi.

Courier cash summani erkin tahrirlamaydi. PINsiz delivered yo'q.

## Partial delivery

Courier actual quantity, sabab, dalolatnoma va media dalil yuboradi. Seller 4 soat
ichida qaror qiladi. Approve bo'lsa actual summa; reject/timeout bo'lsa manual review.
Final qarorgacha ledger ikki marta yoki taxminiy summa bilan yopilmaydi.

## Courier earning

Courier earning delivered mahsulot qiymati va orderga snapshot qilingan masofa bandi
foizidan hisoblanadi. Foiz 0.1%-7% oralig'ida setting bilan boshqariladi.

Courier earning va customerdan olingan naqd liability alohida ledgerlarda yuritiladi.
Ularni yashirincha net qilib bitta mutable balancega aylantirmaslik kerak.

## Cash handover

Trip yakunlangach courier Uvita oldida olgan cash bo'yicha qarzdor:

1. cash handover request yuboradi;
2. expected, oldin topshirilgan va hozirgi summa ko'rsatiladi;
3. accountant real olingan summani tasdiqlaydi;
4. faqat tasdiqdan keyin liability kamayadi.

Kamida 90% topshirilmaguncha yangi trip blok. 90%ga yetgach yangi trip mumkin, lekin
qolgan 10% uchun 3 kun deadline. Deadline o'tsa qarz to'liq yopilguncha blok va alert.

## Query va concurrency talabi

- Offer list pagination/limit bilan va faqat kerakli summary ustunlari bilan.
- Trip detail relationlari bitta rejalashtirilgan eager-load graph bilan.
- Har delivery uchun loop ichida seller/order/address query ochilmaydi.
- Capacity va accept transactionda row lock/unique constraint bilan himoyalanadi.
- Location history listlari vaqt oralig'i va limit bilan; cheklanmagan GPS export yo'q.
- Active offer, route corridor va debt eligibility querylari real datasetda `EXPLAIN`
  bilan tekshiriladi.
- Query-count test tripdagi delivery soni oshganda N+1 bo'lmasligini isbotlaydi.

## Minimal test matrix

- mos va nomos vehicle;
- weight/volume/distance chegaralari;
- parallel accept race;
- 1 soat ichida va undan keyin cancel;
- rejected offer qayta chiqmasligi;
- pickupdan keyin cancel bloklanishi;
- forged/duplicate pickup;
- PINsiz va expired PIN bilan delivery yopilmasligi;
- duplicate confirm ledgerni takrorlamasligi;
- 90% unblock va 3 kun deadline;
- duplicate accountant acceptance;
- offer/trip detail N+1 query regressiyasi.
