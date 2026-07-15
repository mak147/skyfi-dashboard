#!/usr/bin/env sh
set -eu

cd /var/www/backend

INTERVAL="${SKYFI_WORKER_INTERVAL_SECONDS:-60}"

echo "[$(date -Iseconds)] SkyFi supervisor worker started (interval: ${INTERVAL}s)."

while true; do
  # Reserved for production-safe scheduled maintenance commands. Existing
  # business workflows remain HTTP/API driven; this heartbeat lets Supervisor
  # own a stable worker process without mutating application state.
  echo "[$(date -Iseconds)] SkyFi supervisor worker heartbeat."
  sleep "${INTERVAL}"
done
