#!/bin/bash
# Drupal initialization script
# Waits for database and runs necessary setup commands

set -e

# Configuration from environment
DB_HOST="${DRUPAL_DB_HOST:-postgres}"
DB_PORT="${DRUPAL_DB_PORT:-5432}"
DB_NAME="${DRUPAL_DB_NAME:-drupal}"
DB_USER="${DRUPAL_DB_USER:-drupal}"
MAX_RETRIES="${DB_MAX_RETRIES:-30}"
RETRY_INTERVAL="${DB_RETRY_INTERVAL:-2}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Drupal Initialization ===${NC}"

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
retries=0
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" > /dev/null 2>&1; do
    retries=$((retries + 1))
    if [ $retries -ge $MAX_RETRIES ]; then
        echo -e "${RED}ERROR: PostgreSQL not available after ${MAX_RETRIES} attempts${NC}"
        exit 1
    fi
    echo "  Attempt $retries/$MAX_RETRIES - PostgreSQL not ready, waiting ${RETRY_INTERVAL}s..."
    sleep $RETRY_INTERVAL
done

echo -e "${GREEN}PostgreSQL is ready!${NC}"

# Change to Drupal root
cd /var/www/html

# Check if Drupal is already installed
if [ -f "web/sites/default/settings.php" ] && grep -q "^\$databases" "web/sites/default/settings.php" 2>/dev/null; then
    echo "Drupal appears to be installed, running cache rebuild..."
    ./vendor/bin/drush cr || true
else
    echo -e "${YELLOW}Drupal not installed yet.${NC}"
    echo "Please complete installation via the web interface:"
    echo "  1. Navigate to https://drupal.staging.chatthub.online"
    echo "  2. Select 'Radicale Calendar Starter' installation profile"
    echo "  3. Use database credentials from environment"
fi

# Ensure files directory permissions
echo "Setting files directory permissions..."
mkdir -p web/sites/default/files
chmod 755 web/sites/default/files

echo -e "${GREEN}=== Drupal Initialization Complete ===${NC}"
