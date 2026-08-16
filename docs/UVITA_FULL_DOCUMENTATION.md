# Uvita — to‘liq loyiha hujjati

> Ushbu hujjat Uvita loyihasini ilgari ko‘rmagan odam ham tizim nima qilishi, kim qanday ishlatishi va buyurtma boshidan oxirigacha qanday yurishini tushunishi uchun yozilgan.
>
> Holati: 2026-yil 16-avgustdagi amaldagi kod asosida.

## 1. Uvita nima?

Uvita — sotuvchi, xaridor, menejer va kuryerni bitta tizimda bog‘laydigan B2B/B2B2C marketplace.

Tizim faqat fermer yoki poliz mahsulotlari bilan cheklanmaydi. Hozir katalogda oziq-ovqat va qishloq xo‘jaligi mahsulotlari ko‘proq bo‘lishi mumkin, lekin arxitektura keyinchalik ishlab chiqarilgan mahsulotlar, xomashyo, qadoqlangan tovarlar va boshqa ulgurji kategoriyalarni ham qo‘shishga moslangan.

Oddiy qilib aytganda:

1. Seller o‘z do‘koni va mahsulotini tizimga kiritadi.
2. Admin mahsulot ma’lumotlarini tekshiradi.
3. Tasdiqlangan mahsulot marketda ko‘rinadi.
4. Xaridor mahsulotlarni savatga yig‘ib, naqd buyurtma beradi.
5. Tizim mahsulotlarni seller do‘koni bo‘yicha alohida orderlarga ajratadi.
6. Menejer xaridor bilan kelishadi, kerak bo‘lsa orderni tahrirlaydi va tasdiqlaydi.
7. Seller yukni tayyorlaydi, menejer orderni “Tayyor” bosqichiga o‘tkazadi.
8. Kuryer yo‘nalishni tanlaydi, tizim uning sig‘imiga mos orderlar paketini avtomatik beradi.
9. Kuryer yuklarni sellerlardan olib, xaridorlarga yetkazadi va naqd pulni oladi.
10. Har bir order PIN orqali alohida yakunlanadi.

## 2. Eng muhim atamalar

| Atama | Oddiy tushuntirish |
|---|---|
| Customer / xaridor | Marketdan mahsulot tanlab, buyurtma beradigan foydalanuvchi |
| Seller / sotuvchi | Mahsulot joylaydigan va buyurtmani tayyorlaydigan tomon |
| Seller shop / do‘kon | Sellerga tegishli savdo nuqtasi. Bitta sellerda bir nechta do‘kon bo‘lishi mumkin |
| Manager / menejer | Yangi buyurtmani xaridor bilan kelishib, tasdiqlab, tayyor holatga o‘tkazadigan operator |
| Courier / kuryer | Bir yo‘nalishdagi bir nechta yukni sellerlardan olib, xaridorlarga yetkazadigan haydovchi |
| Admin | Seller, mahsulot, kategoriya, order va operatsion jarayonlarni nazorat qiluvchi xodim |
| Super-admin | Barcha huquqlarga, moliya, sozlama va xodim boshqaruviga ega rol |
| Checkout | Xaridor savatdagi mahsulotlar bo‘yicha buyurtma beradigan amal |
| Order | Bitta seller do‘konidan bitta xaridorga tegishli alohida buyurtma |
| Checkout group | Xaridor bir marta buyurtma berganda hosil bo‘lgan bir nechta orderni bog‘lovchi umumiy identifikator |
| Reys / trip | Kuryerning bir boshlang‘ich hududdan bitta yakuniy hududga olib ketadigan orderlar to‘plami |
| Pickup | Kuryer yukni sellerdan oladigan nuqta |
| Delivery | Kuryer yukni xaridorga topshiradigan nuqta |
| Stock | Ombordagi haqiqiy mahsulot miqdori |
| Reserved stock | Buyurtma uchun vaqtincha band qilingan, boshqa xaridorga sotib bo‘lmaydigan miqdor |
| PIN | Yetkazishni tasdiqlash uchun xaridorga beriladigan 4 xonali kod |

## 3. Loyiha qanday qismlardan iborat?

Uvita beshta mustaqil ilova va bitta umumiy ma’lumotlar qatlamidan iborat.

| Qism | Papka | Vazifasi | Texnologiya |
|---|---|---|---|
| Backend API | `uvita_backend` | Barcha biznes qoidalari, ma’lumotlar bazasi, autentifikatsiya, order, stok, moliya va logistika | Laravel 12, PHP 8.4, MySQL 8, Redis |
| Customer market | `uvita_frontend` | Xaridor uchun katalog, savat, checkout, orderlar va profil | React 19, Vite |
| Dashboard | `uvita_frontend_dashboard` | Manager, admin va super-admin paneli | React 19, Vite |
| Seller panel | `uvita_frontend_seller` | Seller do‘koni, mahsulot, sotuv va analitika | React, TypeScript, Vinext |
| Kuryer ilovasi | `Uvita-kuryer` | Kuryer profili, reys, pickup, yetkazish, PIN va naqd pul | Flutter, Dart |

Backend barcha panellar uchun yagona haqiqat manbai hisoblanadi. Frontendda ko‘rsatilgan qiymatlarga ishonib qolmaydi: narx, komissiya, stok, ruxsat va status o‘zgarishlari serverda qayta tekshiriladi.

```text
Customer market ───────┐
Seller panel ──────────┤
Manager/Admin panel ───┼──> Laravel API ───> MySQL
Kuryer ilovasi ────────┤          │
                       └──────────> Redis queue ──> SMS / Telegram / Push
```

## 4. Rollar va ularning vazifalari

### 4.1. Xaridor

Xaridor quyidagilarni qiladi:

- telefon raqami orqali ro‘yxatdan o‘tadi yoki tizimga kiradi;
- viloyat va tumanni tanlaydi;
- mahsulotlarni qidiradi va filtrlaydi;
- mahsulot tavsifi, narxi, rasmlari va minimal miqdorini ko‘radi;
- kerakli miqdorni kg, tonna, dona, litr, quti yoki bog‘ birligida kiritadi;
- mahsulotni savatga qo‘shadi;
- yetkazish manzilini kiritib, naqd buyurtma beradi;
- order statuslarini kuzatadi;
- yetkazish PIN kodini kuryerga aytadi;
- yetkazilgan mahsulotga sharh qoldiradi.

### 4.2. Seller

Seller quyidagilarni qiladi:

- telefon va parol bilan seller panelga kiradi;
- o‘ziga tegishli do‘konni tanlaydi;
- biznes va pickup manzilini to‘ldiradi;
- mahsulot yaratadi yoki mavjud mahsulotni tahrirlaydi;
- stok, narx va minimal buyurtma miqdorini belgilaydi;
- mahsulot media fayllarini yuklaydi;
- moderatsiya holatini kuzatadi;
- o‘z orderlari va sotuvlarini ko‘radi;
- taxminiy sof tushum va analitikani ko‘radi.

### 4.3. Menejer

Menejer naqd orderlar bo‘yicha asosiy operator:

- yangi `pending` orderlarni ko‘radi;
- xaridor bilan telefon orqali kelishadi;
- xaridor fikri o‘zgarsa, pending order mahsulotlari va miqdorini tahrirlaydi;
- orderni tasdiqlaydi;
- seller yukni tayyorlagach orderni `ready_to_deliver` holatiga o‘tkazadi;
- kerak bo‘lsa pending orderni bekor qiladi.

### 4.4. Kuryer

Kuryer bitta order ortidan emas, bitta yo‘nalishdagi bir nechta orderni olib ketadi:

- profiliga transport turi, sig‘imi va maksimal order sonini kiritadi;
- smenani yoqadi;
- masalan, “Jizzax → Toshkent” reysini tanlaydi;
- nechta kg yuk olishga tayyorligini ko‘rsatadi;
- tizim bergan avtomatik orderlar paketini qabul qiladi;
- pickup nuqtalaridan yuklarni ketma-ket oladi;
- barcha yuk olingach xaridor manzillarini ko‘radi;
- har bir orderni alohida yetkazadi;
- PIN va olingan naqd summani kiritadi;
- reys yakunida yig‘ilgan pul, kuryer haqi va platformaga topshiriladigan summani ko‘radi.

### 4.5. Admin

Admin operatsion nazoratni amalga oshiradi:

- seller va do‘kon ma’lumotlarini tekshiradi;
- mahsulot reviziyalarini tasdiqlaydi yoki rad etadi;
- kategoriyalarni boshqaradi;
- orderlarni va muammoli yetkazishlarni kuzatadi;
- kuryerlarni va yordam so‘rovlarini nazorat qiladi;
- sharhlarni moderatsiya qiladi;
- operatsion analitikani ko‘radi;
- manager va kuryer xodimlarini boshqaradi.

### 4.6. Super-admin

Super-admin admin huquqlaridan tashqari:

- moliyaviy analitikani;
- tranzaksiyalarni;
- platforma sozlamalarini;
- kuryer payoutlarini;
- barcha turdagi xodimlarni yaratish va o‘chirishni boshqaradi.

## 5. Mahsulot qanday marketga chiqadi?

### 5.1. Seller profilini tayyorlash

Mahsulot joylashdan oldin sellerning do‘koni tekshirilgan bo‘lishi kerak. Profilga kamida quyidagilar kiritiladi:

- biznes nomi va yuridik turi;
- STIR;
- telefon;
- viloyat, tuman va aniq manzil;
- bank hisob raqami va MFO;
- shartlarga rozilik;
- imkon qadar pickup nuqtasining latitude/longitude koordinatasi.

Koordinata logistika uchun muhim: u bo‘lmasa tizim sellerlarni bir-biriga yaqinligiga qarab aniq tartiblashda qiynaladi.

### 5.2. Mahsulot talablari

Seller mahsulotga quyidagilarni kiritadi:

- nom;
- kategoriya;
- kamida 50 belgili tavsif;
- kelib chiqish yoki ishlab chiqarilgan hudud;
- fermer, xo‘jalik yoki ishlab chiqaruvchi nomi;
- o‘lchov birligi;
- ombordagi miqdor;
- minimal buyurtma miqdori;
- bitta birlik narxi;
- logistika uchun birlik vazni, kerak bo‘lsa;
- rasmlar va video.

Qo‘llanadigan o‘lchov birliklari:

- `kg`;
- `tonna`;
- `dona`;
- `litr`;
- `quti`;
- `bog‘`.

`dona`, `litr`, `quti` va `bog‘` kabi birliklarda `unit_weight_kg` to‘g‘ri kiritilishi kuryer sig‘imini hisoblash uchun juda muhim.

### 5.3. Media cheklovlari

Rasmlar:

- 4 tadan kam va 10 tadan ko‘p bo‘lmasligi kerak;
- bittasi asosiy rasm bo‘ladi;
- JPG, PNG yoki WEBP formatida bo‘lishi kerak;
- har biri 5 MB dan oshmasligi kerak;
- kvadrat, ya’ni 1:1 nisbatda bo‘lishi kerak;
- o‘lchami 800×800 dan kichik va 3000×3000 dan katta bo‘lmasligi kerak.
- barcha rasmlarning piksel o‘lchami bir xil bo‘lishi kerak.

Video:

- kamida bitta real mahsulot videosi majburiy;
- MP4, MOV yoki WEBM formatida;
- 5 MB dan oshmasligi kerak.

Noto‘g‘ri fayl tanlansa panel fayl nega qabul qilinmaganini alohida xabarda ko‘rsatadi.

### 5.4. Moderatsiya

Yangi mahsulot darhol marketga chiqmaydi:

```text
Seller mahsulot yaratadi
        ↓
Mahsulot inactive, reviziya pending bo‘ladi
        ↓
Admin ma’lumot va mediani tekshiradi
        ↓
Tasdiqlasa active bo‘ladi va marketga chiqadi
Rad etsa seller sababni ko‘rib, qayta tuzatadi
```

Faol mahsulot tahrirlanganda eski, tasdiqlangan versiya marketda qoladi. Yangi o‘zgarish alohida reviziya sifatida adminga boradi. Admin tasdiqlagandan keyingina marketdagi versiya yangilanadi. Bu noto‘g‘ri yoki tekshirilmagan ma’lumotning darhol xaridorga chiqib ketishini oldini oladi.

## 6. Xaridor marketi qanday ishlaydi?

Asosiy bo‘limlar:

- **Bosh sahifa** — kategoriyalar, tavsiya va yangi mahsulotlar;
- **Bozor** — qidiruv, kategoriya, hudud va saralash;
- **Mahsulot sahifasi** — media, tavsif, seller, narx, birlik, stok va minimum;
- **Savat** — mahsulot miqdorlarini boshqarish;
- **Checkout** — manzil va naqd buyurtma tasdig‘i;
- **Buyurtmalar** — orderlar ro‘yxati va statusi;
- **Profil** — shaxsiy ma’lumotlar va hudud.

Marketning asosiy vazifasi savdo. E’lon berish, chat yoki eski C2C sahifalari customer navigatsiyasiga kiritilmagan.

### 6.1. Miqdor kiritish

Katta hajmli B2B xaridda `+` tugmasini yuzlab marta bosish talab qilinmaydi. Xaridor qiymatni bevosita kiritishi yoki mos qadamlar bilan oshirishi mumkin.

Seller har bir mahsulot uchun o‘z minimumini belgilaydi. Masalan:

- kartoshka: kamida 100 kg;
- moy: kamida 20 litr;
- qadoq: kamida 10 quti.

Savatda qiymat vaqtincha minimumdan kichik bo‘lishi mumkin, ammo checkout bunga ruxsat bermaydi va qaysi mahsulotdan kamida qancha olish kerakligini yozadi.

### 6.2. Umumiy minimal xarid

Yetkazib berish uchun barcha mahsulotlar yig‘indisi kamida **1 000 000 so‘m** bo‘lishi kerak. Bu qiymat backend sozlamasida `min_order_amount` orqali boshqariladi. Frontend ham ayni paytda 1 000 000 so‘mni ko‘rsatadi, lekin yakuniy tekshiruv doim backendda bajariladi.

### 6.3. To‘lov

Hozirgi faol biznes oqimida faqat **naqd to‘lov** ishlatiladi.

Xaridor marketda:

- mahsulot narxini;
- mahsulotlar jami summasini;
- “Naqd” to‘lov usulini;
- kuryerga qancha to‘lashi kerakligini ko‘radi.

Xaridorga platforma, kuryer, soliq yoki to‘lov tizimi komissiyalari alohida qo‘shilmaydi. U seller qo‘ygan mahsulot narxlari yig‘indisini to‘laydi.

Backendda Payme, Click va Uzum kabi online to‘lovlarga oid kodlar mavjud, ammo ular hozir customerning asosiy savdo oqimida ishlatilmaydi.

## 7. Bitta checkout nega bir nechta orderga ajraladi?

Bu loyihadagi eng muhim qoida.

Xaridor bitta savatga turli sellerlardan mahsulot qo‘shishi mumkin. Checkout tugmasini bir marta bosadi, ammo backend mahsulotlarni **seller do‘koni bo‘yicha** ajratadi.

Misol:

```text
Xaridor savati:
  Jizzaxdagi A do‘konidan olma       1 200 000 so‘m
  Buxorodagi B do‘konidan guruch     2 000 000 so‘m
  Jizzaxdagi A do‘konidan sabzi        800 000 so‘m

Natija:
  Order 1 — A do‘koni: olma + sabzi  2 000 000 so‘m
  Order 2 — B do‘koni: guruch         2 000 000 so‘m
```

Order 1 va Order 2 bir xil `checkout_group_id` bilan bog‘lanadi, ammo ular mustaqil hisoblanadi:

- boshqa seller tayyorlaydi;
- boshqa pickup manzili bor;
- boshqa kuryer olishi mumkin;
- boshqa vaqtda yetib borishi mumkin;
- har biri uchun alohida PIN mavjud;
- har biri uchun naqd pul alohida olinadi;
- biri bekor qilinsa, ikkinchisi davom etishi mumkin.

Demak, “bitta xaridor bitta marta checkout qildi” degani “bitta logistika orderi” degani emas.

## 8. Naqd orderning to‘liq hayot sikli

Asosiy statuslar:

| Texnik status | Paneldagi ma’nosi | Kim o‘tkazadi? |
|---|---|---|
| `pending` | Kelishuv kutilmoqda | Order yaratilganda avtomatik |
| `confirmed` | Tasdiqlangan | Menejer |
| `ready_to_deliver` | Yetkazishga tayyor | Menejer |
| `delivering` | Yetkazilmoqda | Oxirgi pickup tugaganda tizim |
| `delivered` | Yetkazildi | Kuryer PIN va pulni tasdiqlaganda |
| `cancelled` | Bekor qilindi | Xaridor yoki menejer ruxsat etilgan paytda |
| `delivery_issue` | Yetkazishda muammo | Kuryer urinishlari va admin jarayoni |

`paid` statusi online to‘lov oqimi uchun mavjud, lekin naqd orderning oddiy yo‘li undan o‘tmaydi.

### 8.1. 1-bosqich: order yaratiladi

Backend checkout vaqtida:

1. mahsulot faol ekanini tekshiradi;
2. narxni bazadan qayta oladi;
3. quantity seller minimumidan kam emasligini tekshiradi;
4. mavjud stok yetarliligini tekshiradi;
5. umumiy summa platforma minimumiga yetganini tekshiradi;
6. seller do‘koni bo‘yicha alohida orderlar yaratadi;
7. har bir naqd order uchun stokni rezerv qiladi;
8. payment yozuvini yaratadi;
9. savatni shu tranzaksiya ichida tozalaydi;
10. menejerga SMS/Telegram navbatlarini yuboradi.

Yangi order `pending` holatida bo‘ladi va menejer panelida ko‘rinadi.

### 8.2. 2-bosqich: menejer kelishadi

Menejer xaridor bilan bog‘lanadi. Xaridor miqdorni kamaytirish, oshirish, mahsulot qo‘shish yoki olib tashlashni so‘rashi mumkin.

Menejer faqat pending orderni tahrirlaydi. Backend har bir tahrirda:

- mahsulot minimumini;
- stokni;
- yangi summani;
- rezerv miqdorini qayta tekshiradi.

Kelishuv tugagach menejer orderni `confirmed` qiladi.

### 8.3. 3-bosqich: seller yukni tayyorlaydi

Seller o‘z panelida orderni ko‘radi va mahsulotni tayyorlaydi. Operatsion tekshiruvdan so‘ng menejer “Tayyor” amalini bajaradi.

Shunda order `ready_to_deliver` bo‘ladi va kuryer reyslari uchun ochiladi.

### 8.4. 4-bosqich: kuryer reysi

Kuryer yo‘nalishni tanlaydi, tizim unga sig‘imi va limitiga mos orderlarni beradi. U barcha pickup nuqtalaridan yuklarni oladi. Oxirgi pickup tasdiqlanganda orderlar `delivering` holatiga o‘tadi va xaridor manzillari ochiladi.

### 8.5. 5-bosqich: yetkazish

Har bir xaridor uchun kuryer:

1. navigator orqali manzilga boradi;
2. mahsulotni topshiradi;
3. orderda ko‘rsatilgan to‘liq naqd summani oladi;
4. xaridordan 4 xonali PIN oladi;
5. ilovaga PIN va olingan summani kiritadi;
6. tasdiqlaydi.

PIN va naqd summa to‘g‘ri bo‘lsa order `delivered` bo‘ladi. Reysdagi barcha orderlar tugaganda reys ham `completed` bo‘ladi.

## 9. Stok qanday hisoblanadi?

Stokda uchta tushuncha bor:

```text
Mavjud sotiladigan miqdor = stock - reserved_stock
```

Misol:

```text
Omborda stock:                 1 000 kg
Oldingi orderlarga rezerv:       300 kg
Yangi xaridorga mavjud:          700 kg
```

### 9.1. Order yaratilganda

Naqd order yaratilishi bilan `reserved_stock` oshadi. Fizik `stock` hali kamaymaydi. Bu bir vaqtning o‘zida bir xil mahsulotni bir nechta odam ortiqcha sotib yuborishining oldini oladi.

### 9.2. Menejer orderni tahrirlaganda

Miqdor oshsa qo‘shimcha rezerv qilinadi. Miqdor kamaysa ortiqcha rezerv bo‘shatiladi. Amallar baza tranzaksiyasi va blokirovka ichida bajariladi.

### 9.3. Order tasdiqlanganda

`confirmed` bosqichida mahsulot hali rezervda turadi. Fizik ombordan yechilmaydi.

### 9.4. Order “Tayyor” bo‘lganda

Menejer `ready_to_deliver` qilganda:

- fizik `stock` kamayadi;
- shu orderga tegishli `reserved_stock` bo‘shatiladi.

Bu mahsulot ombordan real yuk sifatida ajratilganini anglatadi.

### 9.5. Order bekor bo‘lganda

- Tayyor bo‘lishidan oldin bekor qilinsa faqat rezerv bo‘shatiladi.
- Ombordan yechilgandan keyingi muammo sababli admin bekor qilsa, mahsulot bir marta omborga qaytariladi.

## 10. Moliyaviy hisob-kitob

Hozir ichki hisob mahsulotlarning orderdagi jami summasidan olinadi.

| Qism | Foiz |
|---|---:|
| Platforma haqi | 10% |
| Kuryer haqi | 5% |
| Soliq uchun ushlab qolinadigan qism | 1% |
| To‘lov tizimi uchun ushlab qolinadigan qism | 3% |
| Seller sof tushumi | 81% |

Misol, mahsulotlar jami **1 000 000 so‘m** bo‘lsa:

| Kimga / nima uchun | Summa |
|---|---:|
| Xaridor kuryerga to‘laydi | 1 000 000 so‘m |
| Seller sof tushumi | 810 000 so‘m |
| Platforma asosiy haqi | 100 000 so‘m |
| Kuryer haqi | 50 000 so‘m |
| Soliq uchun qism | 10 000 so‘m |
| To‘lov tizimi uchun qism | 30 000 so‘m |

Hozir barcha to‘lov naqd bo‘lsa ham, mavjud biznes qoidasi bo‘yicha soliq va to‘lov tizimi ulushi ichki hisobda platforma tomonida ushlab turiladi.

Muhim:

- xaridorga 19% ustama qo‘shilmaydi;
- seller narxni yakuniy mahsulot narxi sifatida kiritadi;
- marketda ichki komissiya bo‘linmasi ko‘rsatilmaydi;
- seller panelda taxminiy sof tushum ko‘rsatilishi mumkin;
- yakuniy hisobni backend bajaradi.

### 10.1. Kuryer haqining hozirgi holati

Amaldagi kodda kuryer haqi barcha yo‘nalishlar uchun order summasining **5%**i.

Masofaga qarab 1%–5% oralig‘ida o‘zgaradigan jadval muhokama qilingan, ammo hozircha backendga tatbiq qilinmagan. Shu sababli Jizzax–Sirdaryo va Jizzax–Toshkent orderlari ayni 5% stavkada hisoblanadi. Bu “hozir ishlaydi” deb emas, keyingi bosqich vazifasi deb qaralishi kerak.

## 11. Kuryer reysi qanday ishlaydi?

### 11.1. Reys nima?

Reys — bir xil asosiy yo‘nalishdagi bir nechta orderni bitta kuryerga birlashtiradigan real, bazada saqlanadigan obyekt.

Masalan:

```text
Yo‘nalish: Jizzax → Toshkent

Pickup 1: Jizzax shahri, Seller A — 3 ta order
Pickup 2: Sharof Rashidov tumani, Seller B — 2 ta order

Delivery: Toshkent shahri ichidagi 5 ta xaridor
```

Kuryer “eng foydali” orderlarni bittalab tanlamaydi. U yo‘nalishni va olishga tayyor yuk hajmini tanlaydi; order paketini tizim beradi.

### 11.2. Kuryer profilidagi limitlar

Kuryer profilida:

- transport turi;
- transport raqami;
- maksimal yuk sig‘imi, kg;
- maksimal order soni saqlanadi.

Reys yaratishda so‘ralgan sig‘im profildagi haqiqiy sig‘imdan oshirilmaydi. Bitta reysdagi mahsulotlar umumiy qiymati **50 000 000 so‘mdan oshmaydi**.

### 11.3. Tizim orderlarni nimaga qarab tanlaydi?

Faqat `ready_to_deliver` va boshqa kuryerga biriktirilmagan orderlar tanlanadi.

Tizim quyidagilarni inobatga oladi:

1. tanlangan boshlang‘ich va yakuniy hudud;
2. eng oldin tushgan orderni chiqarishga ustuvorlik;
3. kuryerning kg sig‘imi;
4. kuryerning maksimal order soni;
5. 50 mln so‘mlik xavfsizlik limiti;
6. pickup nuqtalarining bir-biriga yaqinligi;
7. delivery nuqtalarining bir-biriga yaqinligi.

Amaldagi yaqinlik chegaralari:

- asosiy pickup nuqtadan 60 km dan uzoq pickup paketga qo‘shilmaydi;
- asosiy delivery hududidan 80 km dan uzoq delivery paketga qo‘shilmaydi.

Nomzodlarni saralashda pickup masofasiga ko‘proq og‘irlik beriladi. Maqsad kuryerni bir viloyatning ikki qarama-qarshi chetiga sababsiz yubormaslik.

Bir nechta kuryer bir vaqtda reys olsa, baza blokirovkasi bitta orderning ikki kuryerga berilib ketishini oldini oladi.

### 11.4. Vazn hisoblash

- 1 tonna = 1 000 kg;
- 1 kg = 1 kg;
- boshqa birliklar `unit_weight_kg` orqali kg ga o‘giriladi;
- vazn kiritilmagan boshqa birlik hozircha 1 kg deb olinadi.

Shuning uchun sellerdagi birlik vazni noto‘g‘ri bo‘lsa, tizim kuryerga mos bo‘lmagan yuk berishi mumkin.

### 11.5. Pickup jarayoni

Reys yaratilganda status `picking_up` bo‘ladi.

Kuryerga seller/pickup nuqtalari eng yaqin ketma-ketlikda ko‘rsatiladi. Bir pickupda shu seller do‘koniga tegishli bir nechta order bo‘lishi mumkin. Kuryer har bir nuqtada “Oldim” tugmasini bosadi.

Barcha yuklar olinmaguncha:

- xaridorning aniq manzili yashiriladi;
- xaridor telefoni yashiriladi;
- delivery marshruti ochilmaydi.

Oxirgi pickup bajarilganda:

- reys `delivering` holatiga o‘tadi;
- orderlar `delivering` bo‘ladi;
- xaridorlarning yetkazish ma’lumoti ochiladi;
- ilovada barcha yuklar olingani haqida muvaffaqiyat animatsiyasi ko‘rsatiladi.

### 11.6. Delivery jarayoni

Tizim delivery nuqtalarini ham yaqin ketma-ketlikda beradi. Har bir order alohida yakunlanadi.

Kuryer quyidagilarni kiritishi shart:

- xaridorning 4 xonali PIN kodi;
- real olingan naqd summa.

Naqd summa order `grand_total` qiymatiga aynan teng bo‘lishi kerak. PIN noto‘g‘ri kiritilsa urinish sanaladi. Standart sozlama bo‘yicha 5 ta noto‘g‘ri urinishdan keyin 10 daqiqalik blok ishlaydi.

### 11.7. Reysni bekor qilish

Kuryer reysni:

- reys yaratilganidan keyin 5 soat ichida;
- hali birorta pickup olinmagan bo‘lsa

bekor qila oladi. Reys ichidagi alohida orderni tanlab tashlab ketish mumkin emas. Bu kuryerning faqat qulay orderlarni olib, qolganlarini qoldirishini oldini oladi.

### 11.8. Reys yakuni

Barcha orderlar yetkazilganda reys `completed` bo‘ladi va xulosa chiqadi:

```text
Jami yig‘ilgan naqd pul
- Kuryerning jami haqi
= Platformaga topshiriladigan naqd pul
```

Har bir orderning kuryer haqi alohida hisoblangan bo‘lsa-da, reys yakunida umumiy ko‘rinishda jamlanadi.

## 12. Yetkazish hududi qoidasi

Yetkazish joyi ikki turga ajratiladi.

### 12.1. Shahar

Agar tanlangan hudud shahar bo‘lsa, kuryer xaridorning to‘liq manziliga boradi:

- ko‘cha;
- uy;
- mo‘ljal;
- xarita koordinatasi.

Masalan, Toshkent shahri, Yunusobod tumani — shahar ichidagi aniq manzilga yetkaziladi.

### 12.2. Tuman

Agar manzil oddiy tuman bo‘lsa, yetkazish tuman markazigacha hisoblanadi. Kuryerga xususiy uy manzili o‘rniga tuman markazi ko‘rsatiladi.

Bu qoida chekka qishloqlar sabab reys masofasi nazoratsiz oshib ketmasligi uchun ishlatiladi.

## 13. PIN va yetkazish xavfsizligi

- PIN 4 xonali.
- PIN ready/delivering bosqichida xaridorning order sahifasida ko‘rinadi.
- Kuryer orderni PINsiz yakunlay olmaydi.
- PINning noto‘g‘ri urinishlari cheklangan.
- PIN bilan birga naqd summa ham serverda tekshiriladi.
- Xaridorning aniq manzili pickup tugamaguncha kuryerga berilmaydi.
- Order narxlari va kuryer haqi frontenddan emas, serverdagi qiymatlardan olinadi.

Mahsulotni topshirish fotosi yoki elektron imzo hozir majburiy emas. Hozirgi asosiy dalil — PIN, naqd summa va serverdagi status o‘zgarishi.

## 14. Seller panel bo‘limlari

### 14.1. Bosh sahifa

- faol mahsulotlar;
- pending moderatsiyalar;
- yangi orderlar;
- sotuv ko‘rsatkichlari;
- taxminiy sof tushum.

### 14.2. Mahsulotlar

- barcha mahsulotlar ro‘yxati;
- faol, moderatsiyada va rad etilgan holatlar;
- yangi mahsulot qo‘shish;
- faol mahsulot uchun yangi reviziya yuborish;
- admin rad etish sababini ko‘rish.

### 14.3. Sotuvlar / orderlar

Seller faqat o‘zining tanlangan do‘koniga tegishli orderlarni ko‘radi. Boshqa sellerning orderi yoki bir checkoutdagi boshqa do‘kon orderi unga ko‘rinmaydi.

### 14.4. Analitika

- sotilgan mahsulotlar;
- orderlar soni;
- jami savdo;
- komissiyalardan keyingi taxminiy seller tushumi;
- davr bo‘yicha ko‘rsatkichlar.

### 14.5. Profil

- biznes rekvizitlari;
- bank ma’lumotlari;
- hudud va pickup manzili;
- koordinata;
- do‘konlar orasida almashish;
- verifikatsiya holati.

Har bir API so‘rovda tanlangan seller do‘koni `X-Seller-Shop-Id` headeri orqali yuboriladi. Shu bilan bitta sellerning bir nechta do‘koni ma’lumotlari aralashib ketmaydi.

## 15. Dashboard bo‘limlari

### Menejer ko‘radi

- bosh sahifa;
- mahsulotlar;
- orderlar.

### Admin ko‘radi

- menejer bo‘limlari;
- kategoriyalar;
- mahsulot moderatsiyasi;
- xodimlar;
- sellerlar va do‘kon verifikatsiyasi;
- sharhlar;
- operatsion analitika;
- order va kuryer muammolari.

### Super-admin qo‘shimcha ko‘radi

- moliyaviy analitika;
- tranzaksiyalar;
- platforma sozlamalari;
- barcha xodim rollari;
- payout va yuqori darajadagi moliyaviy boshqaruv.

Dashboard kodida kuryer uchun eski web sahifalar ham mavjud. Hozirgi asosiy kuryer oqimi alohida Flutter ilovasidagi trip/reys jarayonidir.

## 16. Sharhlar

Xaridor faqat `delivered` orderdagi mahsulotga sharh qoldira oladi.

Jarayon:

1. xaridor baho va izoh yuboradi;
2. sharh moderatsiya kutadi;
3. admin tasdiqlaydi yoki rad etadi;
4. faqat tasdiqlangan sharh public marketda ko‘rinadi;
5. mahsulot reytingi tasdiqlangan sharhlar asosida yangilanadi.

## 17. Bildirishnomalar va fon vazifalari

Backend Redis queue orqali quyidagi vazifalarni fon rejimida bajaradi:

- yangi order haqida menejerga SMS yoki Telegram;
- order statusi o‘zgarganda kerakli tomonga xabar;
- kuryer push xabarlari;
- yetkazish PIN xabarlari;
- boshqa sekin tashqi integratsiyalar.

Queue worker ishlamasa order bazada yaratilishi mumkin, lekin SMS, Telegram yoki push kechikadi. Shuning uchun backend bilan birga `queue` servisi ham doim ishlashi kerak.

## 18. Qo‘shimcha va eski modullar

Backendda `Listing`, `Chat`, `Deal` va `Rating` modullari ham bor. Ular loyihaning avvalgi C2C/e’lon yo‘nalishidan qolgan imkoniyatlar:

- foydalanuvchi e’loni;
- e’lon bo‘yicha chat;
- kelishuv/deal;
- foydalanuvchi reytingi.

Hozir customer marketning asosiy navigatsiyasi bu sahifalarni ishlatmaydi va eski yo‘llar market yoki order sahifasiga yo‘naltiriladi. Asosiy biznes oqimi `Product → Cart → Order → Trip → Delivery` hisoblanadi. Eski modullarni yangi marketplace logikasining bir qismi deb aralashtirmaslik kerak.

## 19. Asosiy API guruhlari

Mahalliy API bazasi:

```text
http://127.0.0.1:8000/api
```

| Prefix | Kim uchun |
|---|---|
| `/api/products`, `/api/categories` | Public katalog |
| `/api/auth/*`, `/api/cart`, `/api/orders` | Xaridor |
| `/api/seller/*` | Seller |
| `/api/manager/*` | Menejer |
| `/api/admin/*` | Admin |
| `/api/super/*` | Super-admin |
| `/api/courier/*` | Kuryer |

Muhim kuryer trip endpointlari:

```text
GET  /api/courier/trip-routes
POST /api/courier/trips/preview
POST /api/courier/trips
GET  /api/courier/trips/active
PUT  /api/courier/trips/{trip}/pickups/{pickupKey}
PUT  /api/courier/trips/{trip}/orders/{order}/delivered
PUT  /api/courier/trips/{trip}/cancel
```

Muhim manager endpointlari:

```text
GET    /api/manager/orders
GET    /api/manager/orders/{id}
PUT    /api/manager/orders/{id}/items
PUT    /api/manager/orders/{id}/confirm
PUT    /api/manager/orders/{id}/ready
DELETE /api/manager/orders/{id}
```

API javoblari, validatsiya va role middleware haqidagi chuqur texnik ma’lumot backendning `STRUCTURE.md`, `LIFECYCLE.md` va modul route fayllarida mavjud.

## 20. Lokal kompyuterda ishga tushirish

### 20.1. Kerakli dasturlar

- Docker Desktop;
- Git;
- Node.js 22+ va npm;
- Flutter SDK va Android Studio — kuryer ilovasi uchun;
- ixtiyoriy: PHP/Composer, agar Dockersiz ishlatilsa.

### 20.2. Backend

```bash
cd /Users/imac/Project/uvita/uvita_backend
cp .env.example .env
```

Docker uchun `.env`da kamida quyidagilarni MySQLga moslang:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=uvita
DB_USERNAME=uvita
DB_PASSWORD=local_secure_password
```

Keyin:

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Tekshirish:

```bash
curl http://127.0.0.1:8000/up
curl http://127.0.0.1:8000/api/products
```

Backend manzili: `http://127.0.0.1:8000`.

`migrate:fresh --seed` yoki seeder bazadagi jadvallarni tozalashi mumkin. Uni faqat local/test ma’lumotlariga ishlating, production bazada ishlatmang.

### 20.3. Customer market

```bash
cd /Users/imac/Project/uvita/uvita_frontend
cp .env.example .env
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

Manzil: `http://127.0.0.1:5173`.

Market `/api` so‘rovlarini Vite proxy orqali `localhost:8000` backendga yuboradi.

### 20.4. Dashboard

```bash
cd /Users/imac/Project/uvita/uvita_frontend_dashboard
cp .env.example .env
npm install
npm run dev -- --host 0.0.0.0 --port 5174
```

`.env`:

```dotenv
VITE_API_URL=http://127.0.0.1:8000/api
VITE_CUSTOMER_APP_URL=http://127.0.0.1:5173
```

Manzil: `http://127.0.0.1:5174`.

### 20.5. Seller panel

```bash
cd /Users/imac/Project/uvita/uvita_frontend_seller
cp .env.example .env
npm install
npm run dev -- --host 0.0.0.0 --port 5175
```

`.env`:

```dotenv
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_SITE_URL=http://127.0.0.1:5175
```

Manzil: `http://127.0.0.1:5175`.

### 20.6. Kuryer ilovasi

```bash
cd /Users/imac/Project/uvita/Uvita-kuryer
flutter pub get
```

Android emulator uchun:

```bash
flutter run --dart-define=API_BASE=http://10.0.2.2:8000/api
```

USB orqali ulangan haqiqiy Android telefon uchun:

```bash
adb reverse tcp:8000 tcp:8000
flutter run --dart-define=API_BASE=http://127.0.0.1:8000/api
```

Chrome/web tekshiruvi uchun:

```bash
flutter run -d chrome --web-port 8769 --dart-define=API_BASE=http://127.0.0.1:8000/api
```

Telefon va kompyuter bir Wi-Fi tarmog‘ida bo‘lsa, `API_BASE`ga kompyuterning LAN IP manzilini kiriting. Kuryer profilidagi “Server manzili” orqali API manzilini rebuild qilmasdan ham almashtirish mumkin.

## 21. Local test akkauntlari

> Bu akkauntlar faqat seeder ishlatilgan local/test baza uchun. Productionda foydalanmang.

### Xaridorlar

Parol: `market123`

| Telefon | Foydalanuvchi | Hudud |
|---|---|---|
| `+998901111111` | Diyorbek Karimov | Jizzax shahri |
| `+998902222222` | Madina Ismoilova | Toshkent, Yunusobod |
| `+998903333333` | Akmal Rasulov | Samarqand shahri |

### Xodimlar

Parol: `password123`

| Login | Rol |
|---|---|
| `super@uvita.uz` | Super-admin |
| `admin@uvita.uz` | Admin |
| `manager@uvita.uz` | Menejer |
| `seller@uvita.uz` yoki `+998901234567` | Seller |
| `courier@uvita.uz` | Kuryer |
| `courier2@uvita.uz` | Ikkinchi kuryer |

Seller panel telefon va parol bilan kiradi. Dashboard xodimlari email va parol bilan kiradi.

## 22. Test va sifat tekshiruvi

### Backend

```bash
cd /Users/imac/Project/uvita/uvita_backend
docker compose exec app php artisan test
```

Yoki lokal PHP muhiti sozlangan bo‘lsa:

```bash
php artisan test
```

### Customer market

```bash
cd /Users/imac/Project/uvita/uvita_frontend
npm run lint
npm run build
```

### Dashboard

```bash
cd /Users/imac/Project/uvita/uvita_frontend_dashboard
npm run lint
npm run build
```

### Seller panel

```bash
cd /Users/imac/Project/uvita/uvita_frontend_seller
npm run lint
npm test
```

### Kuryer

```bash
cd /Users/imac/Project/uvita/Uvita-kuryer
flutter analyze
flutter test
```

### Tavsiya etilgan to‘liq E2E ssenariy

1. Seller sifatida kirib, tekshirilgan do‘konni tanlang.
2. 4 ta to‘g‘ri rasm va 5 MB dan kichik video bilan mahsulot yarating.
3. Admin sifatida reviziyani tekshirib tasdiqlang.
4. Customer sifatida mahsulotni marketdan toping.
5. Seller minimumidan kam qiymat bilan checkout qilib, xatoni tekshiring.
6. Miqdorni to‘g‘rilab, savatni 1 000 000 so‘mdan oshiring.
7. Ikkinchi seller mahsulotini ham qo‘shib, naqd checkout qiling.
8. Customerda seller do‘konlari bo‘yicha alohida orderlar chiqqanini tekshiring.
9. Menejerda har ikkala pending order ko‘rinishini tekshiring.
10. Bitta order miqdorini tahrirlang, keyin ikkalasini confirmed va ready qiling.
11. Stok rezervi order yaratilganda, fizik stok esa ready bosqichida o‘zgarganini tekshiring.
12. Kuryer profiliga sig‘im va maksimal order sonini kiriting.
13. Kuryer yo‘nalishni tanlab, preview va reys yaratsin.
14. Orderlarni kuryer qo‘lda tanlay olmasligini tekshiring.
15. Pickup nuqtalarini ketma-ket “Oldim” qiling.
16. Oxirgi pickupgacha customer manzillari yashirin ekanini tekshiring.
17. Customer order sahifasidan 4 xonali PINni oling.
18. Kuryerda noto‘g‘ri PIN va noto‘g‘ri naqd summa xatolarini tekshiring.
19. To‘g‘ri PIN va summada har bir orderni yakunlang.
20. Customer, seller, manager, admin va kuryer tarixida yakuniy status va summalarni solishtiring.

## 23. Xavfsizlik va ma’lumot yaxlitligi

- Customer tokenlari va staff/seller/courier tokenlari alohida guardlar bilan ishlaydi.
- Role middleware foydalanuvchini boshqa panel APIlariga kiritmaydi.
- Seller shop headeri sellerning boshqa do‘kon ma’lumotiga tasodifan aralashishini oldini oladi.
- Narx, komissiya va jami summa serverda hisoblanadi.
- Order yaratish, tahrirlash, stok rezervi va trip biriktirish tranzaksiya ichida bajariladi.
- Database lock parallel buyurtma va parallel kuryer qabulida kolliziyani kamaytiradi.
- PIN urinishlari cheklangan.
- Xaridorning aniq manzili pickup tugamaguncha yashiriladi.
- Upload turi, hajmi, soni va rasm o‘lchami validatsiyadan o‘tadi.
- Productionda `APP_DEBUG=false`, HTTPS, kuchli parol va maxfiy kalitlar talab qilinadi.

## 24. Productionga chiqarish bo‘yicha qisqa qoida

1. Backend uchun production `.env` yarating; secretlarni Gitga yozmang.
2. MySQL va Redisni doimiy volume bilan ishga tushiring.
3. `APP_DEBUG=false` qiling.
4. HTTPS va to‘g‘ri domain/CORS konfiguratsiyasini o‘rnating.
5. Migratsiyani backupdan keyin bajaring.
6. Queue worker va scheduler doim ishlayotganini tekshiring.
7. Frontendlarni production API URL bilan build qiling.
8. Upload storage va public linkni tekshiring.
9. SMS, Telegram va Firebase secretlarini production qiymatlariga almashtiring.
10. Database va media uchun muntazam backup o‘rnating.

Batafsil backend deploy qoidalari `uvita_backend/PRODUCTION_RUNBOOK.md` faylida.

## 25. Hozirgi cheklovlar va keyingi ishlar

Quyidagilar xato sifatida yashirilmasligi, jamoaga ochiq aytilishi kerak:

1. **Kuryer haqi masofaga bog‘liq emas.** Hozir barcha orderda 5%.
2. **Online to‘lov asosiy oqimda yoqilmagan.** Hozir faqat naqd.
3. **Marshrut sifati koordinataga bog‘liq.** Seller pickup yoki customer delivery koordinatasi bo‘lmasa aniqlik pasayadi.
4. **Noto‘g‘ri birlik vazni sig‘im hisobini buzadi.** `unit_weight_kg` majburiy biznes nazoratiga aylantirilishi kerak.
5. **Tizim kuryerni avtomatik tayinlamaydi.** Kuryer reys yo‘nalishini tanlaydi, keyin tizim paketni avtomatik tanlaydi.
6. **Yetkazish fotosi/imzosi majburiy emas.** Asosiy tasdiq PIN va naqd summa.
7. **Eski courier order endpointlari mavjud.** Yangi ilova trip endpointlarini ishlatadi; eski direct accept/reject oqimi faqat moslik uchun qolgan.
8. **Eski Listing/Chat/Deal modullari backendda bor.** Hozir marketplace asosiy oqimiga ulanmagan.

## 26. Tez-tez uchraydigan muammolar

### Frontend APIga ulanmayapti

- Backend `http://127.0.0.1:8000`da ishlayotganini tekshiring.
- `localhost` va `127.0.0.1` aralash ishlatilganda CORS/referrer muammosiga e’tibor bering.
- Dashboard `VITE_API_URL`, seller `NEXT_PUBLIC_API_URL`, market `VITE_API_BASE` qiymatini tekshiring.
- Android emulator uchun `127.0.0.1` kompyuter emas, emulatorning o‘zi; `10.0.2.2` ishlatiladi.

### Order menejerga ko‘rinmayapti

- order haqiqatan yaratilganini;
- status `pending` ekanini;
- manager tokeni va rolini;
- checkout request javobidagi alohida orderlarni tekshiring.

### Stok yetarli ko‘rinsa ham checkout xato beryapti

Faqat `stock`ga qaramang. Sotilishi mumkin bo‘lgan qiymat `stock - reserved_stock`.

### Kuryerga reys chiqmayapti

- order `ready_to_deliver` bo‘lishi kerak;
- order boshqa kuryer/reysga biriktirilmagan bo‘lishi kerak;
- origin/destination tanlangan yo‘nalishga mos bo‘lishi kerak;
- kuryer sig‘imi order vazniga yetishi kerak;
- 50 mln limit va maksimal order sonini tekshiring;
- kuryerning smenasi/profili to‘ldirilganini tekshiring.

### Kuryer customer manzilini ko‘rmayapti

Bu normal xavfsizlik qoidasi bo‘lishi mumkin. Reysdagi barcha pickup nuqtalari “Oldim” qilinmaguncha delivery manzillari ochilmaydi.

### “Yetkazish PIN kodi majburiy” xatosi

Kuryer customer order sahifasida ko‘rsatilgan 4 xonali PINni va aniq naqd summani kiritishi kerak.

### Rasm yoki video qabul qilinmayapti

Format, 5 MB limit, rasmning 1:1 nisbati, 800–3000 piksel chegarasi va rasmlar sonini tekshiring. Panel xabari aniq rad sababini ko‘rsatadi.

## 27. Bir daqiqalik xulosa

Uvita’da seller mahsulot yaratadi, admin uni tasdiqlaydi, customer naqd buyurtma beradi. Bitta checkout seller do‘koni bo‘yicha alohida orderlarga bo‘linadi. Order yaratilganda stok rezerv qilinadi. Menejer customer bilan kelishib orderni tahrirlaydi, tasdiqlaydi va tayyor qiladi; fizik stok aynan tayyor bosqichida kamayadi. Kuryer bir yo‘nalishni tanlaydi, tizim uning sig‘imi, order soni, yuk qiymati, navbat va yaqinlik asosida bir nechta orderni avtomatik beradi. Kuryer avval barcha sellerlardan yukni oladi, keyin customer manzillari ochiladi. Har bir order alohida PIN va naqd summa bilan yakunlanadi.

Asosiy oqim:

```text
Seller → Mahsulot → Admin moderatsiya → Market → Savat → Naqd checkout
       → Seller bo‘yicha alohida order → Menejer kelishuvi → Tayyor
       → Kuryer reysi → Pickup → Delivery → PIN + naqd → Yakun
```

## 28. Bog‘liq texnik hujjatlar

- `uvita_backend/STRUCTURE.md` — backend arxitekturasi va modullar;
- `uvita_backend/LIFECYCLE.md` — umumiy backend lifecycle;
- `uvita_backend/docs/ORDER_LIFECYCLE.md` — order tafsilotlari;
- `uvita_backend/docs/COURIER_TRIP_FLOW.md` — kuryer trip oqimi;
- `uvita_backend/PRODUCTION_RUNBOOK.md` — production ishga tushirish;
- `uvita_frontend_seller/docs/SELLER_PANEL.md` — seller panel tafsilotlari;
- `Uvita-kuryer/README.md` — Flutter kuryer ilovasini ishga tushirish.
