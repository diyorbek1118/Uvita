#!/bin/sh

set -eu

env_file="${1:-.env.production}"

if [ ! -f "$env_file" ]; then
    echo "Production env topilmadi: $env_file" >&2
    exit 1
fi

get_value() {
    key="$1"
    sed -n "s/^${key}=//p" "$env_file" | tail -n 1
}

required_keys="
APP_KEY
APP_URL
DB_DATABASE
DB_USERNAME
DB_PASSWORD
CORS_ALLOWED_ORIGINS
SMS_DRIVER
TELEGRAM_BOT_TOKEN
TELEGRAM_MANAGER_CHAT_IDS
TELEGRAM_ADMIN_CHAT_IDS
TELEGRAM_COURIER_CHAT_IDS
PAYME_MERCHANT_ID
PAYME_KEY
CLICK_SERVICE_ID
CLICK_MERCHANT_ID
CLICK_SECRET_KEY
UZUM_SERVICE_ID
UZUM_USERNAME
UZUM_PASSWORD
"

failed=0
for key in $required_keys; do
    value="$(get_value "$key")"
    if [ -z "$value" ]; then
        echo "Majburiy production qiymati bo'sh: $key" >&2
        failed=1
    fi
done

if [ "$(get_value APP_ENV)" != "production" ]; then
    echo "APP_ENV=production bo'lishi kerak." >&2
    failed=1
fi

if [ "$(get_value APP_DEBUG)" != "false" ]; then
    echo "APP_DEBUG=false bo'lishi kerak." >&2
    failed=1
fi

case "$(get_value APP_URL)" in
    https://*) ;;
    *)
        echo "APP_URL HTTPS bo'lishi kerak." >&2
        failed=1
        ;;
esac

if [ "$(get_value PAYMENT_TEST_MODE)" != "false" ]; then
    echo "PAYMENT_TEST_MODE=false bo'lishi kerak." >&2
    failed=1
fi

case "$(get_value SMS_DRIVER)" in
    eskiz)
        for key in ESKIZ_EMAIL ESKIZ_PASSWORD; do
            if [ -z "$(get_value "$key")" ]; then
                echo "SMS_DRIVER=eskiz uchun $key majburiy." >&2
                failed=1
            fi
        done
        ;;
    http)
        for key in SMS_HTTP_URL SMS_HTTP_TOKEN; do
            if [ -z "$(get_value "$key")" ]; then
                echo "SMS_DRIVER=http uchun $key majburiy." >&2
                failed=1
            fi
        done
        ;;
    *)
        echo "Production muhitida SMS_DRIVER eskiz yoki http bo'lishi kerak." >&2
        failed=1
        ;;
esac

if [ "$(get_value TELEGRAM_REQUIRED)" != "true" ]; then
    echo "TELEGRAM_REQUIRED=true bo'lishi kerak." >&2
    failed=1
fi

if [ "$(get_value QUEUE_CONNECTION)" != "redis" ]; then
    echo "QUEUE_CONNECTION=redis bo'lishi kerak." >&2
    failed=1
fi

if [ "$(get_value QUEUE_AFTER_COMMIT)" != "true" ]; then
    echo "QUEUE_AFTER_COMMIT=true bo'lishi kerak." >&2
    failed=1
fi

if [ "$(get_value CACHE_STORE)" != "redis" ] || [ "$(get_value SESSION_DRIVER)" != "redis" ]; then
    echo "CACHE_STORE va SESSION_DRIVER redis bo'lishi kerak." >&2
    failed=1
fi

case "$(get_value CORS_ALLOWED_ORIGINS)" in
    *http://*)
        echo "CORS_ALLOWED_ORIGINS faqat HTTPS manzillardan iborat bo'lishi kerak." >&2
        failed=1
        ;;
esac

if [ "$failed" -ne 0 ]; then
    exit 1
fi

echo "Production env tekshiruvi muvaffaqiyatli."
