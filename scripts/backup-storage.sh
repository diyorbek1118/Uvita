#!/bin/sh

set -eu

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
compose_file="${COMPOSE_FILE:-$project_dir/docker-compose.production.yml}"
env_file="${ENV_FILE:-$project_dir/.env.production}"
backup_dir="${BACKUP_DIR:-$project_dir/backups}"
retention_days="${BACKUP_RETENTION_DAYS:-14}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="$backup_dir/uvita-storage-$timestamp.tar.gz"
temporary="$target.tmp"

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

cleanup() {
    rm -f "$temporary"
}

trap cleanup EXIT INT TERM

mkdir -p "$backup_dir"

if ! PRODUCTION_ENV_FILE="$env_file" compose --env-file "$env_file" -f "$compose_file" exec -T app \
    tar -C /var/www/storage/app -czf - public > "$temporary"; then
    echo "Upload fayllari backup qilinmadi." >&2
    exit 1
fi

tar -tzf "$temporary" >/dev/null
mv "$temporary" "$target"
write_checksum "$target" > "$target.sha256"

find "$backup_dir" -type f \( -name 'uvita-storage-*.tar.gz' -o -name 'uvita-storage-*.tar.gz.sha256' \) \
    -mtime "+$retention_days" -delete

echo "$target"
