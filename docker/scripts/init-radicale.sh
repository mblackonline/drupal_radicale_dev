#!/bin/bash
# Initialize Radicale data directory and admin user
# Run this script before first deployment or to reset admin password

set -e

# Configuration
RADICALE_DATA_DIR="${RADICALE_DATA_DIR:-/data}"
ADMIN_USER="${RADICALE_ADMIN_USER:-admin}"
ADMIN_PASSWORD="${RADICALE_ADMIN_PASSWORD:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Radicale Initialization ===${NC}"

# Create data directories
echo "Creating data directories..."
mkdir -p "${RADICALE_DATA_DIR}/collections"

# Check if password is provided
if [ -z "$ADMIN_PASSWORD" ]; then
    echo -e "${RED}ERROR: RADICALE_ADMIN_PASSWORD environment variable is required${NC}"
    echo "Usage: RADICALE_ADMIN_PASSWORD=yourpassword ./init-radicale.sh"
    exit 1
fi

# Check if htpasswd command is available
if ! command -v htpasswd &> /dev/null; then
    echo -e "${YELLOW}htpasswd not found, installing apache2-utils...${NC}"
    apt-get update && apt-get install -y apache2-utils
fi

# Create or update htpasswd file with bcrypt
HTPASSWD_FILE="${RADICALE_DATA_DIR}/users"

if [ -f "$HTPASSWD_FILE" ]; then
    echo "Updating existing admin user..."
    htpasswd -Bb "$HTPASSWD_FILE" "$ADMIN_USER" "$ADMIN_PASSWORD"
else
    echo "Creating htpasswd file with admin user..."
    htpasswd -Bbc "$HTPASSWD_FILE" "$ADMIN_USER" "$ADMIN_PASSWORD"
fi

# Set permissions
chmod 600 "$HTPASSWD_FILE"
chmod 755 "${RADICALE_DATA_DIR}/collections"

echo -e "${GREEN}=== Radicale Initialization Complete ===${NC}"
echo ""
echo "Admin user: $ADMIN_USER"
echo "htpasswd file: $HTPASSWD_FILE"
echo ""
echo -e "${YELLOW}IMPORTANT: Store the admin password securely!${NC}"
echo "This password is needed in Drupal's Radicale Calendar Settings."
