#!/bin/sh

set -eu

if [ "$#" -ne 1 ]; then
    echo "Foydalanish: RESTORE_CONFIRM=uvita-storage-restore $0 backups/uvita-storage-....tar.gz" >&2
    exit 1
fi

if [ "${RESTORE_CONFIRM:-}" != "uvita-storage-restore" ]; then
    echo "Tiklash uchun RESTORE_CONFIRM=uvita-storage-restore talab qilinadi." >&2
    exit 1
fi

backup_file="$1"
if [ ! -f "$backup_file" ]; then
    echo "Storage backup fayli topilmadi: $backup_file" >&2
    exit 1
fi

tar -tzf "$backup_file" >/dev/null
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
restore_target="${RESTORE_TARGET:-/var/www/storage/app}"

case "$restore_target" in
    /var/www/storage/app) ;;
    /var/www/storage/app/uvita_restore_test_*) ;;
    *)
        echo "RESTORE_TARGET production storage yoki uvita_restore_test_ prefiksli test papkasi bo‘lishi kerak." >&2
        exit 1
        ;;
esac

compose() {
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T app \
    mkdir -p "$restore_target"

gzip -dc "$backup_file" | PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T \
    -e RESTORE_TARGET="$restore_target" app sh -c 'exec tar -C "$RESTORE_TARGET" -xf -'

echo "Upload fayllari tiklandi: $restore_target"
