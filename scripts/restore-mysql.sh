#!/bin/sh

set -eu

if [ "$#" -ne 1 ]; then
    echo "Foydalanish: RESTORE_CONFIRM=uvita-restore $0 backups/uvita-....sql.gz" >&2
    exit 1
fi

if [ "${RESTORE_CONFIRM:-}" != "uvita-restore" ]; then
    echo "Tiklash uchun RESTORE_CONFIRM=uvita-restore talab qilinadi." >&2
    exit 1
fi

backup_file="$1"
if [ ! -f "$backup_file" ]; then
    echo "Backup fayli topilmadi: $backup_file" >&2
    exit 1
fi

gzip -t "$backup_file"
if [ -f "$backup_file.sha256" ]; then
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum -c "$backup_file.sha256"
    else
        shasum -a 256 -c "$backup_file.sha256"
    fi
fi

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
compose_file="${COMPOSE_FILE:-$project_dir/docker-compose.production.yml}"
env_file="${ENV_FILE:-$project_dir/.env.production}"
restore_database="${RESTORE_DATABASE:-}"

compose() {
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

if [ -n "$restore_database" ]; then
    case "$restore_database" in
        uvita_restore_test_*) ;;
        *)
            echo "RESTORE_DATABASE faqat uvita_restore_test_ prefiksi bilan test tiklash uchun ishlatiladi." >&2
            exit 1
            ;;
    esac
    case "$restore_database" in
        *[!A-Za-z0-9_]*)
            echo "RESTORE_DATABASE faqat harf, raqam va pastki chiziqdan iborat bo‘lishi kerak." >&2
            exit 1
            ;;
    esac

    PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T \
        -e RESTORE_DATABASE="$restore_database" mysql sh -c \
        'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$RESTORE_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
    gzip -dc "$backup_file" | PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T \
        -e RESTORE_DATABASE="$restore_database" mysql sh -c \
        'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$RESTORE_DATABASE"'
else
    gzip -dc "$backup_file" | PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T mysql sh -c \
        'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
fi

echo "Backup tiklandi."
