#!/bin/bash
# Run a miniCal cron method via Docker Compose (production stack).
# Usage: minical-cron.sh <method> [extra CLI args...]
set -euo pipefail

MINICAL_ROOT="/opt/minical"
COMPOSE_FILE="${MINICAL_ROOT}/docker/docker-compose.prod.yaml"
ENV_FILE="${MINICAL_ROOT}/.env"
LOG_DIR="${MINICAL_ROOT}/logs/cron"

METHOD="${1:?Usage: minical-cron.sh <method> [args...]}"
shift

# Methods that require CRON_AUTH_SECRET as a URI segment when the secret is set.
AUTH_METHODS=(
	ota_booking_retrieval
	run_automated_night_audit
	release_inventory_blocks
	import_booking_com_ical
	siteminder_booking_retrieval
)

mkdir -p "$LOG_DIR"
LOG_FILE="${LOG_DIR}/${METHOD}.log"

CRON_AUTH_SECRET=""
if [[ -f "$ENV_FILE" ]]; then
	CRON_AUTH_SECRET="$(grep -m1 '^CRON_AUTH_SECRET=' "$ENV_FILE" | cut -d= -f2- | tr -d " '\"")"
fi

needs_auth=0
for m in "${AUTH_METHODS[@]}"; do
	if [[ "$METHOD" == "$m" ]]; then
		needs_auth=1
		break
	fi
done

CLI=(cron "$METHOD")
if [[ $needs_auth -eq 1 && -n "$CRON_AUTH_SECRET" ]]; then
	CLI+=( "$CRON_AUTH_SECRET" )
fi
CLI+=( "$@" )

{
	echo "===== $(date -Is) cron ${METHOD} ====="
	cd "$MINICAL_ROOT"
	docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" exec -T php \
		sh -c "cd /app/public && exec php index.php $(printf '%q ' "${CLI[@]}")"
} >>"$LOG_FILE" 2>&1
