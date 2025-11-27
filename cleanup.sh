#!/bin/bash
# =============================================================================
# Drupal + Radicale CalDAV Stack - Cleanup Script
# =============================================================================
#
# This script removes the Docker stack and optionally cleans up volumes.
#
# Usage:
#   ./cleanup.sh           # Remove stack, keep data volumes
#   ./cleanup.sh --all     # Remove stack AND delete all data volumes
#
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

STACK_NAME="drupal-calendar"

echo -e "${BLUE}"
echo "============================================="
echo " Drupal + Radicale CalDAV Stack Cleanup"
echo "============================================="
echo -e "${NC}"

# Check if stack exists
if ! docker stack ls 2>/dev/null | grep -q "$STACK_NAME"; then
    echo -e "${YELLOW}Stack '$STACK_NAME' is not deployed.${NC}"
else
    echo -e "${YELLOW}Removing stack '$STACK_NAME'...${NC}"
    docker stack rm "$STACK_NAME"
    
    echo "Waiting for services to stop..."
    sleep 10
    
    # Wait for services to fully stop
    while docker service ls 2>/dev/null | grep -q "$STACK_NAME"; do
        echo "  Services still stopping..."
        sleep 5
    done
    
    echo -e "${GREEN}Stack removed${NC}"
fi

# Handle --all flag for volume cleanup
if [ "$1" == "--all" ]; then
    echo ""
    echo -e "${RED}WARNING: This will delete ALL data including:${NC}"
    echo "  - PostgreSQL database (all Drupal content)"
    echo "  - Drupal uploaded files"
    echo "  - Radicale calendars and user data"
    echo ""
    read -p "Are you sure you want to delete all data? (type 'yes' to confirm): " CONFIRM
    
    if [ "$CONFIRM" == "yes" ]; then
        echo ""
        echo -e "${YELLOW}Removing volumes...${NC}"
        
        # List of volumes to remove
        VOLUMES=(
            "${STACK_NAME}_postgres_data"
            "${STACK_NAME}_drupal_files"
            "${STACK_NAME}_radicale_data"
        )
        
        for VOL in "${VOLUMES[@]}"; do
            if docker volume ls -q | grep -q "^${VOL}$"; then
                echo "  Removing volume: $VOL"
                docker volume rm "$VOL" 2>/dev/null || true
            fi
        done
        
        echo -e "${GREEN}Volumes removed${NC}"
    else
        echo -e "${YELLOW}Volume cleanup cancelled${NC}"
    fi
fi

# Clean up any dangling resources
echo ""
echo -e "${YELLOW}Cleaning up dangling resources...${NC}"
docker system prune -f --filter "label=com.docker.stack.namespace=$STACK_NAME" 2>/dev/null || true

echo ""
echo -e "${GREEN}============================================="
echo " Cleanup Complete!"
echo "=============================================${NC}"
echo ""

if [ "$1" != "--all" ]; then
    echo "Data volumes were preserved. To remove them, run:"
    echo -e "  ${YELLOW}./cleanup.sh --all${NC}"
    echo ""
    echo "To redeploy the stack:"
    echo -e "  ${YELLOW}docker stack deploy -c docker-stack.yml $STACK_NAME${NC}"
fi
