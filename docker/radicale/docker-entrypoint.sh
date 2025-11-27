#!/bin/sh
# =============================================================================
# Radicale Entrypoint - Auto-generates htpasswd from environment variable
# =============================================================================

set -e

HTPASSWD_FILE="/data/users"
CONFIG_FILE="/config/config"

# Create htpasswd file if it doesn't exist and password is provided
if [ ! -f "$HTPASSWD_FILE" ] && [ -n "$RADICALE_ADMIN_PASSWORD" ]; then
    echo "Creating htpasswd file for admin user..."
    mkdir -p /data
    htpasswd -b -B -c "$HTPASSWD_FILE" "${RADICALE_ADMIN_USER:-admin}" "$RADICALE_ADMIN_PASSWORD"
    chown radicale:radicale "$HTPASSWD_FILE"
    chmod 600 "$HTPASSWD_FILE"
    echo "htpasswd file created."
elif [ -f "$HTPASSWD_FILE" ]; then
    echo "htpasswd file already exists."
else
    echo "WARNING: No RADICALE_ADMIN_PASSWORD set and no htpasswd file exists."
    echo "Authentication may fail."
fi

# Ensure data directory has correct permissions
chown -R radicale:radicale /data 2>/dev/null || true

# Start Radicale (use the same command as base image)
exec /venv/bin/python3 -m radicale --config "$CONFIG_FILE"
