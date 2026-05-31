#!/bin/bash
# Install miniCal cron entries for the current user (run once on EC2).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RUNNER="${SCRIPT_DIR}/minical-cron.sh"

chmod +x "$RUNNER"

CRON_FILE="$(mktemp)"
cat >"$CRON_FILE" <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# miniCal — Docker Compose production stack (docker/docker-compose.prod.yaml)
# Logs: /opt/minical/logs/cron/*.log

# Channex OTA booking retrieval (every 2 minutes)
*/2 * * * * ${RUNNER} ota_booking_retrieval

# Automated night audit (every 15 minutes)
*/15 * * * * ${RUNNER} run_automated_night_audit

# Release expired inventory blocks (every 15 minutes)
*/15 * * * * ${RUNNER} release_inventory_blocks

# Booking.com iCal import (every 30 minutes)
*/30 * * * * ${RUNNER} import_booking_com_ical

# Hourly hooks (TTLock reconciliation, extension hourly-cron)
0 * * * * ${RUNNER} hourly

# Daily extension hooks
0 2 * * * ${RUNNER} daily

# Platform SaaS trial expiry
10 2 * * * ${RUNNER} expire_platform_trials

# Channex PCI card token cleanup after checkout
20 2 * * * ${RUNNER} delete_channex_credit_cards

# Channex OTA XML log cleanup
30 3 * * 0 ${RUNNER} delete_ota_xml_logs

EOF

crontab "$CRON_FILE"
rm -f "$CRON_FILE"
echo "Installed crontab for $(whoami):"
crontab -l
