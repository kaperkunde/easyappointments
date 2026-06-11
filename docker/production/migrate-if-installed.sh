#!/bin/bash

# -----------------------------------------------------------------------------
# Apply pending database migrations when the app is already installed.
# -----------------------------------------------------------------------------

set -uo pipefail

MAX_ATTEMPTS="${MIGRATE_DB_WAIT_ATTEMPTS:-60}"
SLEEP_SECONDS="${MIGRATE_DB_WAIT_SECONDS:-2}"

for ((attempt = 1; attempt <= MAX_ATTEMPTS; attempt++)); do
    /usr/local/bin/check-db-installed.php
    status=$?

    case "$status" in
        0)
            echo "Applying pending database migrations..."
            if ! php /var/www/html/index.php console migrate; then
                echo "Error: Database migration failed." >&2
                exit 1
            fi
            echo "Database migrations complete."
            exit 0
            ;;
        1)
            echo "Database reachable but app not installed; skipping migrations."
            exit 0
            ;;
        2)
            echo "Waiting for database (${attempt}/${MAX_ATTEMPTS})..."
            sleep "$SLEEP_SECONDS"
            ;;
        *)
            echo "Unexpected database check status (${status}); skipping migrations."
            exit 0
            ;;
    esac
done

echo "Error: Database not reachable after ${MAX_ATTEMPTS} attempts; aborting startup." >&2

exit 1
