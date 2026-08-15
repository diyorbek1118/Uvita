# Uvita buyurtma va yetkazish jarayoni

Ushbu hujjat xaridor buyurtma yuborgan vaqtdan mahsulot yetkazilib, naqd to‘lov yopilguncha bo‘lgan amaldagi jarayonni tushuntiradi.

## Asosiy model

Uvita savatchasi bitta checkout bo‘lishi mumkin, lekin logistika birligi — alohida `order`.

- Bir do‘kon/seller mahsulotlari bitta orderga birlashadi.
- Turli seller yoki seller do‘konlari mahsulotlari alohida orderlarga ajraladi.
- Bir checkoutdan yaratilgan orderlar umumiy `checkout_group_id` bilan bog‘lanadi.
- Har bir orderning alohida summasi, payment yozuvi, stok rezervi va kuryeri bor.

```text
Savatcha
  ├─ Navoiy selleri → Order #101 → Kuryer A → alohida naqd to‘lov
  ├─ Jizzax selleri → Order #102 → Kuryer B → alohida naqd to‘lov
  └─ Buxoro selleri → Order #103 → Kuryer C → alohida naqd to‘lov
```

Hozirgi faol to‘lov usuli — naqd. Multi-seller checkout onlayn to‘lov bilan vaqtincha bloklangan.

## Buyurtma yaratilishi

Xaridor checkoutda yetkazish manzili, telefon, yetkazish vaqti va savatdagi miqdorlarni yuboradi. Backend bitta tranzaksiya ichida:

1. Mahsulotlarni bloklab o‘qiydi va faol ekanini tekshiradi.
2. Miqdorni sotish uchun mavjud stok bilan solishtiradi.
3. Seller belgilagan minimal mahsulot miqdorini tekshiradi.
4. Butun savatcha platformadagi minimal umumiy summaga yetganini tekshiradi.
5. Mahsulotlarni seller do‘koni bo‘yicha guruhlaydi.
6. Har bir guruhga alohida order va naqd payment yaratadi.
7. Miqdorni `reserved_stock`ga qo‘shadi.
8. Savatchani tozalaydi va managerga xabar yuboradi.

Sotish uchun mavjud miqdor:

```text
available_stock = stock - reserved_stock
```

Buyurtma yaratilganda haqiqiy `stock` kamaymaydi. Mahsulot manager qarorigacha boshqa xaridorga sotilib ketmasligi uchun rezerv qilinadi.

## Manager jarayoni

Naqd buyurtma dastlab `pending` holatida manager paneliga tushadi. Manager:

- xaridor bilan bog‘lanadi;
- buyurtma tarkibi va miqdorini tahrirlaydi;
- buyurtmani tasdiqlaydi;
- mijoz fikridan qaytsa bekor qiladi;
- mahsulot yig‘ilgach `Tayyor` holatiga o‘tkazadi.

Tarkib tahrirlanganda eski rezerv qaytarilib, yangi tarkib uchun rezerv qayta hisoblanadi. Boshqa seller mahsulotini mavjud orderga qo‘shib bo‘lmaydi — u alohida order bo‘lishi kerak.

Manager bekor qilsa order `cancelled` bo‘ladi va foydalanilmagan rezerv qaytariladi.

Manager `Tayyor` qilsa:

- rezerv qilingan miqdor haqiqiy stokdan ayriladi;
- shu miqdor `reserved_stock`dan chiqariladi;
- `stock_committed_at` yoziladi;
- order `ready_to_deliver` bo‘ladi va kuryer reyslarida ko‘rinadi.

## Kuryer reysi

Reys hozircha alohida jadval emas, bir yo‘nalishdagi tayyor orderlarning virtual guruhi.

- Bir xil jo‘nash va yetkazish yo‘nalishidagi orderlar bitta reys ko‘rinishida guruhlanadi.
- Kuryer mashinasi sig‘imini o‘zi bilganligi sababli kerakli orderlarni o‘zi belgilaydi.
- Kuryer bitta yoki bir nechta orderni bir urinishda qabul qilishi mumkin.
- Har bir order mustaqil qoladi: alohida oluvchi, summa, PIN va naqd hisob.

Kuryer orderni qabul qilganda assignment `accepted` bo‘ladi. Assignment vaqtiga nisbatan 5 soat ichida sabab ko‘rsatib voz kechish mumkin. Voz kechilgan order yana `ready_to_deliver` holatida reysga qaytadi. 5 soatdan keyin manager/admin aralashuvi talab qilinadi.

Kuryer mahsulotni olib yo‘lga chiqqanda order `delivering` holatiga o‘tadi. Tafsilotda seller/pickup nuqtalari, xaridor manzili va bog‘lanish ma’lumotlari beriladi.

## Yetkazishni yakunlash

Kuryer xaridor manziliga borgach:

1. Har bir order summasini alohida naqd oladi.
2. Har bir orderni alohida yakunlaydi.
3. Xaridor bergan 4 xonali PIN, qabul qiluvchi va mavjud koordinatalarni yuboradi.
4. PIN to‘g‘ri bo‘lsa order `delivered`, naqd payment esa `paid` bo‘ladi.

Xaridor topilmasa kuryer sabab va koordinata bilan `not-found` yuboradi. Order `delivery_issue` holatiga o‘tadi. Admin uni qayta yetkazishga yuborishi yoki bekor qilishi mumkin. Tayyor bosqichida stokdan chiqarilgan order butunlay bekor qilinsa, stok qaytariladi.

## Holatlar ketma-ketligi

```text
pending → confirmed → ready_to_deliver → delivering → delivered
```

Qo‘shimcha yo‘llar:

- `pending → cancelled`: mijoz/manager bekor qilgan, rezerv qaytariladi.
- `ready_to_deliver → ready_to_deliver`: kuryer voz kechgan, assignment almashadi.
- `delivering → delivery_issue`: xaridor yoki manzil bilan muammo.
- `delivery_issue → ready_to_deliver`: qayta yetkazish.
- `delivery_issue → cancelled`: yakuniy bekor qilish va committed stokni qaytarish.

`paid` holati onlayn oqim uchun mavjud, ammo hozirgi operatsion rejim naqd to‘lovdir.

## Moliyaviy hisob

Market xaridorga faqat mahsulotlarning yakuniy narxini ko‘rsatadi. Ichki hisob seller kiritgan summadan olinadi:

- platforma: 10%;
- kuryer: hozircha 5% gacha;
- soliq: 1%;
- to‘lov tizimi: 3%;
- standart seller sof tushumi: 81%.

Kuryer foizini masofaga qarab hisoblash keyingi bosqich vazifasi. Hozir standart 5% qo‘llanadi. Naqd ishlayotgan bo‘lsa ham 3% ichki konfiguratsiyada platforma tomonidan ushlab qolinadi.

## Asosiy API endpointlar

Xaridor:

- `POST /api/orders` — checkout va sellerlar bo‘yicha order yaratish.
- `GET /api/orders` / `GET /api/orders/{id}` — orderlarni ko‘rish.
- `DELETE /api/orders/{id}` — ruxsat etilgan bosqichda bekor qilish.
- `GET /api/orders/{id}/delivery-code` — yetkazish PIN kodi.

Manager:

- `GET /api/manager/orders` — ishlov beriladigan buyurtmalar.
- `PUT /api/manager/orders/{id}/items` — pending tarkibni tahrirlash.
- `PUT /api/manager/orders/{id}/confirm` — tasdiqlash.
- `PUT /api/manager/orders/{id}/ready` — yig‘ildi/tayyor.
- `DELETE /api/manager/orders/{id}` — mijoz nomidan bekor qilish.

Kuryer:

- `GET /api/courier/routes` — virtual reyslar.
- `PUT /api/courier/routes/accept` — tanlangan orderlar guruhini olish.
- `PUT /api/courier/orders/{id}/accept` — mahsulotni olib, yetkazishni boshlash.
- `PUT /api/courier/orders/{id}/reject` — 5 soat ichida voz kechish.
- `PUT /api/courier/orders/{id}/delivered` — PIN bilan yakunlash.
- `PUT /api/courier/orders/{id}/not-found` — muammoni qayd etish.

Admin:

- `PUT /api/admin/orders/{id}/assign-courier` — qo‘lda kuryer tayinlash.
- `PUT /api/admin/orders/{id}/resolve-issue` — yetkazish muammosini hal qilish.

## Deploy talablari

Yangi stok va fulfillment ustunlari uchun deploy vaqtida:

```bash
php artisan migrate --force
```

Queue worker SMS, Telegram va savatchani asinxron tozalash vazifalari uchun doimiy ishlashi kerak. Productionda scheduler, queue va log monitoring ham yoqilgan bo‘lishi kerak.

