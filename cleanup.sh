#!/bin/bash
# Complete cleanup script for Drupal devenv project
# This script will reset everything to a fresh state

echo "=== Drupal DevEnv Complete Cleanup ==="
echo "This will:"
echo "  - Stop all running services"
echo "  - Remove all databases"
echo "  - Delete Drupal installation files"
echo "  - Clear all cached data"
echo "  - Fix any permission issues"
echo "  - Recreate required directories with proper permissions"
echo ""
read -p "Are you sure you want to completely reset? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cleanup cancelled."
    exit 1
fi

echo ""
echo "Step 1: Stopping all processes..."
sudo pkill -f process-compose 2>/dev/null
sleep 1
sudo pkill -9 -f process-compose 2>/dev/null
pkill -f devenv 2>/dev/null
pkill -f php 2>/dev/null
pkill -f radicale 2>/dev/null
pkill -f postgres 2>/dev/null
sleep 2

echo "Step 2: Aggressive PostgreSQL cleanup..."
# Kill ALL PostgreSQL processes more aggressively
sudo pkill -f postgres 2>/dev/null
sudo pkill -f postgresql 2>/dev/null
sudo systemctl stop postgresql 2>/dev/null || true
sleep 2

# Clear any PostgreSQL data directories
echo "  - Clearing PostgreSQL temp files..."
sudo rm -rf /var/lib/postgresql/*/main/ 2>/dev/null || true
sudo rm -rf /tmp/postgresql* 2>/dev/null || true

echo "Step 3: Cleaning socket files..."
sudo rm -rf /run/user/$(id -u)/devenv-* 2>/dev/null

echo "Step 4: Fixing ownership issues..."
# Fix ownership of any root-owned files before removal
if [ -d ".devenv" ]; then
    echo "  - Fixing .devenv ownership..."
    sudo chown -R $(whoami):$(whoami) .devenv/ 2>/dev/null || true
fi

if [ -d "web/web/sites/default" ]; then
    echo "  - Fixing web/sites/default ownership..."
    sudo chown -R $(whoami):$(whoami) web/web/sites/default/ 2>/dev/null || true
fi

echo "Step 5: Removing ALL devenv state (including databases)..."
rm -rf .devenv

echo "Step 6: Cleaning Drupal installation..."
# Remove Drupal files (should now work without sudo)
rm -rf web/web/sites/default/files
rm -f web/web/sites/default/settings.php
rm -f web/web/sites/default/settings.local.php
rm -f web/web/sites/default/services.yml
rm -f web/web/sites/default/services.local.yml

echo "Step 7: Recreating required directories..."
# Create files directory with proper permissions
mkdir -p web/web/sites/default/files
chmod 755 web/web/sites/default/files

# Restore default settings file if it exists
if [ -f web/web/sites/default/default.settings.php ]; then
    echo "  - Restoring default.settings.php..."
    cp web/web/sites/default/default.settings.php web/web/sites/default/settings.php
    chmod 666 web/web/sites/default/settings.php
else
    echo "  - Warning: default.settings.php not found"
fi

echo "Step 8: Cleaning composer dependencies..."
rm -rf web/vendor
rm -f web/composer.lock

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "Important: Never run devenv commands with sudo!"
echo "This can cause permission issues."
echo ""
echo "To start fresh:"
echo "  1. Close this terminal completely"
echo "  2. Open a new terminal"
echo "  3. Navigate to the project directory"
echo "  4. Run: chmod +x setup.sh cleanup.sh"
echo "  5. Run: ./setup.sh"
echo "  6. Run: devenv shell"
echo "  7. Run: cd web && composer install && cd .."
echo "  8. Run: devenv up -d"
echo "  9. Visit http://127.0.0.1:8000 to install Drupal"
echo ""
echo "Database credentials for fresh install:"
echo "  - Host: 127.0.0.1"
echo "  - Port: 5432"
echo "  - Database: drupal"
echo "  - Username: drupaluser"
echo "  - Password: drupalpass"
