# Uvita texnik hujjatlari

Hujjatlar bitta ma'lumotni bir necha joyda takrorlamaslik uchun qismlarga ajratilgan.

## Asosiy manbalar

1. [`../LIFECYCLE.md`](../LIFECYCLE.md) - biznesning yagona asosiy lifecycle'i.
2. [`../AGENTS.md`](../AGENTS.md) - arxitektura, xavfsizlik, query va test qoidalari.
3. [`../STRUCTURE.md`](../STRUCTURE.md) - repo va modul chegaralari.

## Focused hujjatlar

- [`ORDER_LIFECYCLE.md`](ORDER_LIFECYCLE.md) - order, stock va status o'tishlari.
- [`COURIER_TRIP_FLOW.md`](COURIER_TRIP_FLOW.md) - offer, trip, pickup, delivery va cash.

## Muhim qoida

Focused hujjat `LIFECYCLE.md`ga zid bo'lsa `LIFECYCLE.md` ustuvor va focused hujjat
shu ishning o'zida tuzatiladi. Eski online-payment, ombor yoki `paid -> confirmed`
oqimi cash-first birinchi reliz uchun asos qilib olinmaydi.

API endpointlarning eng aniq joriy ro'yxati modul ichidagi route fayllarida. Hujjatda
kodda yo'q endpointni mavjud deb ko'rsatmaslik kerak.
