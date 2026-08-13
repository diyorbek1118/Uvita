#!/bin/sh

set -eu

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
compose_file="${COMPOSE_FILE:-$project_dir/docker-compose.production.yml}"
env_file="${ENV_FILE:-$project_dir/.env.production}"
backup_dir="${BACKUP_DIR:-$project_dir/backups}"
retention_days="${BACKUP_RETENTION_DAYS:-14}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="$backup_dir/uvita-$timestamp.sql.gz"
temporary="$target.tmp"
raw_temporary="$backup_dir/.uvita-$timestamp.sql.tmp"

cleanup() {
    rm -f "$temporary" "$raw_temporary"
}

trap cleanup EXIT INT TERM

compose() {
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

write_checksum() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1"
    else
        shasum -a 256 "$1"
    fi
}

mkdir -p "$backup_dir"

if ! PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T mysql sh -c \
    'exec mysqldump --single-transaction --quick --no-tablespaces --routines --triggers --events -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
    > "$raw_temporary"; then
    echo "MySQL backup yaratilmadi." >&2
    exit 1
fi

if ! gzip -9 < "$raw_temporary" > "$temporary"; then
    echo "MySQL backup siqilmadi." >&2
    exit 1
fi

gzip -t "$temporary"
mv "$temporary" "$target"
write_checksum "$target" > "$target.sha256"
rm -f "$raw_temporary"

find "$backup_dir" -type f \( -name 'uvita-*.sql.gz' -o -name 'uvita-*.sql.gz.sha256' \) \
    -mtime "+$retention_days" -delete

echo "$target"
