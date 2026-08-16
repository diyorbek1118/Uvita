# Kuryer reysi: ishlash tartibi

## Maqsad

Kuryer alohida foydali buyurtmalarni tanlamaydi. U faqat yo‘nalishni tanlaydi,
tizim esa yuk sig‘imi, zakaz limiti, 50 mln so‘mlik xavfsizlik limiti, masofa va
navbat asosida reys paketini avtomatik shakllantiradi.

## Buyurtma tanlash qoidalari

1. Faqat `ready_to_deliver` va hali kuryeri yo‘q buyurtmalar olinadi.
2. Bitta reysda jo‘nash va yetkazish viloyati bir xil bo‘ladi.
3. Eng oldin tushgan buyurtma reysning boshlang‘ich nuqtasi bo‘ladi.
4. Qolgan buyurtmalar pickup yaqinligi, delivery yaqinligi va tushgan vaqti
   bo‘yicha saralanadi.
5. Umumiy og‘irlik profilning `vehicle_capacity_kg` qiymatidan oshmaydi.
6. Zakazlar soni `max_orders_per_trip` qiymatidan oshmaydi.
7. Reysdagi jami naqd yuk qiymati 50 000 000 so‘mdan oshmaydi.
8. Pickup va delivery ketma-ketligi nearest-neighbour usulida tuziladi.
9. Ikki parallel so‘rov bir buyurtma yoki bir kuryer uchun ikki reys yarata
   olmaydi: tanlash transaction va database lock ichida bajariladi.

`kg` to‘g‘ridan-to‘g‘ri, `tonna` 1000 kg sifatida hisoblanadi. Dona, litr va
boshqa birliklar uchun mahsulotdagi `unit_weight_kg` ishlatiladi.

## Holatlar

```text
picking_up -> delivering -> completed
     |
     +-----> cancelled (5 soat ichida va pickup boshlanmagan bo‘lsa)
```

- `picking_up`: seller/pickup nuqtalari ketma-ket ko‘rsatiladi.
- `delivering`: barcha yuklar olingan, xaridorlar manzili ochilgan.
- `completed`: barcha buyurtmalar PIN va naqd summa bilan yakunlangan.
- `cancelled`: buyurtmalar yana bo‘sh reyslar ro‘yxatiga qaytarilgan.

Reysdagi bitta zakazni alohida rad etish mumkin emas. Bu cherry-pick qilish va
reysning qolgan yukini buzishni oldini oladi.

## Manzil maxfiyligi

Oxirgi pickup olinmaguncha API xaridor telefoni, aniq manzili va koordinatasini
umuman yubormaydi. Kuryer faqat reysning umumiy destination hududini ko‘radi.

- Shahar (`city`) yetkazishida to‘liq ko‘cha, uy va navigator koordinatasi
  ochiladi.
- Tuman (`district_center`) yetkazishida faqat viloyat, tuman va “Tuman
  markazi” ko‘rsatiladi; xaridorning uy manzili kuryerga berilmaydi.

## Naqd hisob

Har delivery yakunida kuryerga aynan `grand_total` miqdorida olinadigan summa
ko‘rsatiladi. Server boshqa summani qabul qilmaydi. PIN to‘g‘ri bo‘lsa naqd
payment `paid` bo‘ladi va reys hisobiga qo‘shiladi.

```text
platformaga topshiriladi = mijozlardan olingan naqd - kuryer haqi
```

Kuryer haqi hozir buyurtmada oldindan hisoblangan `courier_fee` qiymatidan
olinadi. Masofaga bog‘liq foizni alohida modul hisoblaydi.

## API

- `GET /api/courier/trip-routes` — mavjud yo‘nalishlar.
- `POST /api/courier/trips/preview` — tizim tanlaydigan paket xulosasi.
- `POST /api/courier/trips` — reysni atomar yaratish.
- `GET /api/courier/trips/active` — faol reys.
- `PUT /api/courier/trips/{trip}/pickups/{pickupKey}` — pickupdagi barcha
  yuklarni “oldim” qilish.
- `PUT /api/courier/trips/{trip}/orders/{order}/delivered` — naqd summa va
  4 xonali PIN bilan delivery yakunlash.
- `PUT /api/courier/trips/{trip}/cancel` — pickup boshlanmagan reysni 5 soat
  ichida bekor qilish.

Eski `PUT /api/courier/routes/accept` endpointi olib tashlangan: u kuryerga
zakaz IDlarini qo‘lda tanlash imkonini berardi.

## Ma’lumot sifati

Aniq masofa uchun seller profilida `pickup_latitude/pickup_longitude`, orderda
delivery koordinatasi bo‘lishi kerak. Eski seller koordinatasi yo‘q bo‘lsa tizim
bir xil tuman nomini yaqin deb oladi va neytral masofa bilan navbatni saqlaydi.
Bu holatda eng eski buyurtma baribir birinchi tanlanadi.
