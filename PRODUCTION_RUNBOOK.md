# Uvita production runbook

## Deploydan oldin

1. `.env.production.example` dan `.env.production` yarating.
2. Barcha majburiy qiymatlarni kiriting va `scripts/validate-production-env.sh` bilan tekshiring.
3. Domainning HTTPS reverse proxy sozlamasida backendni faqat `127.0.0.1:8000` ga yo'naltiring.
4. Frontendda `VITE_API_URL=https://api.example.uz/api` ni production build vaqtida kiriting.
5. Birinchi reliz cash-only. Online payment va fiskal providerlarni productionda
   alohida biznes/buxgalter tasdig'isiz yoqmang.

## Deploy

```bash
ENV_FILE=.env.production scripts/deploy-production.sh
```

Skript immutable release tegli backend va nginx image yaratadi, migratsiyani bajaradi,
app/queue/scheduler xizmatlarini yangilaydi va `/up` health endpointini tekshiradi.

Deploydan keyin operator review, stock reservation, seller ready, courier pickup,
delivery PIN, seller ledger va courier cash handover bo'yicha smoke test o'tkazing.
Queue failure, scheduler va ledger reconciliation alertlarini tekshirmasdan trafficni
to'liq ochmang.

## Backup

```bash
ENV_FILE=.env.production scripts/backup-mysql.sh
ENV_FILE=.env.production scripts/backup-storage.sh
```

MySQL va upload backup'lari `backups/` ichiga siqilgan arxiv hamda SHA-256 fayli bilan yoziladi.

## Restore testi

Restore avval alohida test bazasida tekshirilishi kerak:

```bash
RESTORE_CONFIRM=uvita-restore \
RESTORE_DATABASE=uvita_restore_test_20260724 \
ENV_FILE=.env.production \
scripts/restore-mysql.sh backups/uvita-YYYYMMDDTHHMMSSZ.sql.gz
```

Production bazaga to'g'ridan-to'g'ri tiklashdan oldin servisni maintenance holatiga o'tkazing,
joriy bazadan yana bir backup oling va `RESTORE_DATABASE` ni bermasdan restore skriptini ishlating.

Upload backupini avval alohida test papkasiga tiklang:

```bash
RESTORE_CONFIRM=uvita-storage-restore \
RESTORE_TARGET=/var/www/storage/app/uvita_restore_test_20260724 \
ENV_FILE=.env.production \
scripts/restore-storage.sh backups/uvita-storage-YYYYMMDDTHHMMSSZ.tar.gz
```

Tekshiruvdan keyin production storage'ga tiklashda `RESTORE_TARGET` ni bermang.

## Rollback

```bash
ENV_FILE=.env.production scripts/rollback-production.sh
```

Bu ilova image'ini oldingi release'ga qaytaradi. Ma'lumotlar bazasi migratsiyasini avtomatik
orqaga qaytarmaydi; ma'lumot yo'qotishi mumkin bo'lgan DB rollback faqat tekshirilgan backup
orqali qo'lda bajariladi.
