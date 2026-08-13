#!/bin/sh

set -eu

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$project_dir"

if [ ! -f .deploy/previous-release ]; then
    echo "Oldingi release topilmadi." >&2
    exit 1
fi

env_file="${ENV_FILE:-.env.production}"
release_tag="$(cat .deploy/previous-release)"
health_url="${HEALTH_URL:-http://127.0.0.1:${APP_PORT:-8000}/up}"

compose() {
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

PRODUCTION_ENV_FILE="$env_file" APP_IMAGE_TAG="$release_tag" compose --env-file "$env_file" -f docker-compose.production.yml up -d app queue scheduler nginx

attempt=0
until curl -fsS "$health_url" >/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Rollback health check muvaffaqiyatsiz." >&2
        exit 1
    fi
    sleep 2
done

printf '%s\n' "$release_tag" > .deploy/current-release
echo "Rollback muvaffaqiyatli: $release_tag"
