#!/bin/sh

set -eu

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$project_dir"

env_file="${ENV_FILE:-.env.production}"
compose_file="docker-compose.production.yml"
release_tag="${RELEASE_TAG:-$(git rev-parse --short=12 HEAD)}"
health_url="${HEALTH_URL:-http://127.0.0.1:${APP_PORT:-8000}/up}"

compose() {
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

scripts/validate-production-env.sh "$env_file"
mkdir -p .deploy

if [ -f .deploy/current-release ]; then
    cp .deploy/current-release .deploy/previous-release
fi

PRODUCTION_ENV_FILE="$env_file" APP_IMAGE_TAG="$release_tag" compose --env-file "$env_file" -f "$compose_file" build app nginx
PRODUCTION_ENV_FILE="$env_file" APP_IMAGE_TAG="$release_tag" compose --env-file "$env_file" -f "$compose_file" run --rm app php artisan migrate --force
PRODUCTION_ENV_FILE="$env_file" APP_IMAGE_TAG="$release_tag" compose --env-file "$env_file" -f "$compose_file" up -d --remove-orphans
PRODUCTION_ENV_FILE="$env_file" APP_IMAGE_TAG="$release_tag" compose --env-file "$env_file" -f "$compose_file" exec -T app php artisan optimize

attempt=0
until curl -fsS "$health_url" >/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Health check muvaffaqiyatsiz. Rollback skriptini ishga tushiring." >&2
        exit 1
    fi
    sleep 2
done

printf '%s\n' "$release_tag" > .deploy/current-release
echo "Deploy muvaffaqiyatli: $release_tag"
