#!/bin/bash
# =============================================================================
# Drupal + Radicale CalDAV Stack - Setup Script
# =============================================================================
# 
# This script prepares the environment for Docker Swarm deployment.
# 
# Usage:
#   ./setup.sh              # Interactive setup
#   ./setup.sh --generate   # Generate secrets and create .env
#
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================="
echo " Drupal + Radicale CalDAV Stack Setup"
echo "============================================="
echo -e "${NC}"

# Check prerequisites
echo -e "${YELLOW}Checking prerequisites...${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}ERROR: Docker is not installed${NC}"
    echo "Install Docker: https://docs.docker.com/engine/install/"
    exit 1
fi

if ! docker info 2>/dev/null | grep -q "Swarm: active"; then
    echo -e "${RED}ERROR: Docker Swarm is not initialized${NC}"
    echo "Initialize Swarm: docker swarm init"
    exit 1
fi

if ! docker network ls | grep -q "traefik_net"; then
    echo -e "${YELLOW}Creating traefik_net network...${NC}"
    docker network create --driver=overlay --attachable traefik_net
fi

echo -e "${GREEN}Prerequisites OK${NC}"
echo ""

# Check for .env file
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}No .env file found.${NC}"
    
    if [ "$1" == "--generate" ]; then
        echo "Generating .env with random secrets..."
        
        # Generate random secrets
        POSTGRES_PASSWORD=$(openssl rand -base64 32 | tr -d '\n/+=')
        RADICALE_PASSWORD=$(openssl rand -base64 32 | tr -d '\n/+=')
        DRUPAL_HASH_SALT=$(openssl rand -base64 64 | tr -d '\n')
        
        cat > .env << EOF
# Generated on $(date)
# =============================================================================
# Drupal + Radicale CalDAV Stack - Environment Configuration
# =============================================================================

# PostgreSQL Database
POSTGRES_DB=drupal
POSTGRES_USER=drupal
POSTGRES_PASSWORD=${POSTGRES_PASSWORD}

# Radicale CalDAV Server
RADICALE_ADMIN_USER=admin
RADICALE_ADMIN_PASSWORD=${RADICALE_PASSWORD}

# Drupal Settings
DRUPAL_HASH_SALT=${DRUPAL_HASH_SALT}
DRUPAL_TRUSTED_HOST=drupal.staging.chatthub.online
EOF
        
        chmod 600 .env
        echo -e "${GREEN}.env file created with generated secrets${NC}"
        echo ""
        echo -e "${YELLOW}IMPORTANT: Save these credentials securely!${NC}"
        echo "  PostgreSQL Password: ${POSTGRES_PASSWORD}"
        echo "  Radicale Password:   ${RADICALE_PASSWORD}"
        echo ""
    else
        echo "Creating .env from template..."
        cp .env.example .env
        echo -e "${YELLOW}Please edit .env and set secure passwords:${NC}"
        echo "  nano .env"
        echo ""
        echo "Or run with --generate to auto-generate secrets:"
        echo "  ./setup.sh --generate"
        exit 1
    fi
else
    echo -e "${GREEN}.env file exists${NC}"
fi

# Source environment
set -a
source .env
set +a

# Validate required variables
echo ""
echo -e "${YELLOW}Validating configuration...${NC}"

MISSING=0
for VAR in POSTGRES_PASSWORD RADICALE_ADMIN_PASSWORD DRUPAL_HASH_SALT; do
    if [ -z "${!VAR}" ] || [ "${!VAR}" == "CHANGE_ME_USE_STRONG_PASSWORD" ] || [ "${!VAR}" == "CHANGE_ME_GENERATE_RANDOM_HASH_SALT" ]; then
        echo -e "${RED}ERROR: $VAR is not set or still has default value${NC}"
        MISSING=1
    fi
done

if [ $MISSING -eq 1 ]; then
    echo ""
    echo "Please update .env with proper values or run: ./setup.sh --generate"
    exit 1
fi

echo -e "${GREEN}Configuration valid${NC}"

# Initialize Radicale users
echo ""
echo -e "${YELLOW}Initializing Radicale...${NC}"

# Create radicale data directory structure
mkdir -p radicale_init
cat > radicale_init/init-users.sh << 'INITSCRIPT'
#!/bin/sh
# Create htpasswd file with admin user
apk add --no-cache apache2-utils
mkdir -p /data/collections
htpasswd -Bbc /data/users "$RADICALE_ADMIN_USER" "$RADICALE_ADMIN_PASSWORD"
chmod 600 /data/users
echo "Radicale users initialized"
INITSCRIPT
chmod +x radicale_init/init-users.sh

echo -e "${GREEN}Radicale initialization prepared${NC}"

# Set up Drupal directories
echo ""
echo -e "${YELLOW}Setting up Drupal directories...${NC}"

mkdir -p web/web/sites/default/files
chmod 755 web/web/sites/default/files

if [ -f web/web/sites/default/default.settings.php ] && [ ! -f web/web/sites/default/settings.php ]; then
    cp web/web/sites/default/default.settings.php web/web/sites/default/settings.php
    chmod 666 web/web/sites/default/settings.php
fi

echo -e "${GREEN}Drupal directories ready${NC}"

# Build instructions
echo ""
echo -e "${BLUE}============================================="
echo " Setup Complete!"
echo "=============================================${NC}"
echo ""
echo "Next steps:"
echo ""
echo "  1. Build the Docker image:"
echo -e "     ${YELLOW}docker build -t drupal-radicale:latest -f docker/drupal/Dockerfile .${NC}"
echo ""
echo "  2. Initialize Radicale admin user:"
echo -e "     ${YELLOW}docker run --rm -v drupal-calendar_radicale_data:/data \\
       -e RADICALE_ADMIN_USER=admin \\
       -e RADICALE_ADMIN_PASSWORD=\$RADICALE_ADMIN_PASSWORD \\
       alpine sh -c 'apk add apache2-utils && mkdir -p /data/collections && htpasswd -Bbc /data/users admin \$RADICALE_ADMIN_PASSWORD'${NC}"
echo ""
echo "  3. Deploy the stack:"
echo -e "     ${YELLOW}docker stack deploy -c docker-stack.yml drupal-calendar${NC}"
echo ""
echo "  4. Check deployment status:"
echo -e "     ${YELLOW}docker service ls${NC}"
echo ""
echo "  5. Access your applications:"
echo "     Drupal:   https://drupal.staging.chatthub.online"
echo "     Radicale: https://radicale.staging.chatthub.online"
echo ""

# Cleanup temp files
rm -rf radicale_init
