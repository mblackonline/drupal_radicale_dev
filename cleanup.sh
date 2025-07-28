#!/bin/bash
# Complete cleanup script for Drupal devenv project
# This script will reset everything to a fresh state

echo "=== Drupal DevEnv Complete Cleanup ==="
echo "This will:"
echo "  - Stop all running services"
echo "  - Remove PostgreSQL remnants (socket files, processes)"
echo "  - Delete Drupal installation files"
echo "  - Clear all cached data"
echo "  - Fix any permission issues"
echo "  - Recreate required directories with proper permissions"
echo ""
echo "WARNING: This will stop PostgreSQL processes and remove socket files."
echo "IMPORTANT: Exit the devenv shell before running this script!"
echo ""
if [ -n "$DEVENV_STATE" ] || [ -n "$DEVENV_ROOT" ]; then
    echo "❌ ERROR: You are currently in a devenv shell!"
    echo "Please exit the devenv shell first by typing: exit"
    echo "Then run this script again."
    exit 1
fi
echo ""
read -p "Are you sure you want to completely reset? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cleanup cancelled."
    exit 1
fi

# Detect OS for platform-specific commands
OS_TYPE=$(uname -s)
PROJECT_DIR=$(basename "$(pwd)")

echo ""
echo "Step 1: Stopping devenv and project processes..."
# Stop devenv processes first
pkill -f "devenv.*$PROJECT_DIR" 2>/dev/null || true
pkill -f process-compose 2>/dev/null || true
sleep 2

echo "Step 2: Cleaning up PostgreSQL remnants..."

# Kill PostgreSQL processes that might be hanging around
if [ -d ".devenv/state/postgres" ]; then
    echo "  - Stopping project PostgreSQL processes..."
    # Kill postgres processes specifically related to this project
    pkill -f "postgres.*$(pwd)" 2>/dev/null || true
    pkill -f "postgres.*\.devenv" 2>/dev/null || true
fi

# Clean up PostgreSQL socket files
echo "  - Removing stale PostgreSQL socket files..."
# Look for socket files that might be preventing startup
rm -f /tmp/.s.PGSQL.* 2>/dev/null || true
rm -f /tmp/.PGSQL.* 2>/dev/null || true

# Platform-specific PostgreSQL temp cleanup
if [ "$OS_TYPE" = "Linux" ]; then
    # Only clean postgres temp files, not all temp files
    find /tmp -name "postgresql*" -user "$(whoami)" -delete 2>/dev/null || true
    
    # Stop system postgresql only if we're on Linux and it might interfere
    # Only try this if systemctl exists and we can stop it without affecting other users
    if command -v systemctl >/dev/null 2>&1; then
        systemctl --user stop postgresql 2>/dev/null || true
    fi
elif [ "$OS_TYPE" = "Darwin" ]; then
    # macOS-specific cleanup
    find /tmp -name "postgresql*" -user "$(whoami)" -delete 2>/dev/null || true
    # Clean up any postgres socket files in user directories
    rm -f ~/Library/Application\ Support/Postgres/* 2>/dev/null || true
fi

echo "Step 3: Cleaning socket and runtime files..."
# Remove devenv-specific socket files
rm -rf "/run/user/$(id -u)/devenv-$PROJECT_DIR"* 2>/dev/null || true
rm -rf "/tmp/devenv-$PROJECT_DIR"* 2>/dev/null || true

echo "Step 4: Fixing ownership issues..."
# Fix ownership within project directory only
if [ -d ".devenv" ]; then
    echo "  - Fixing .devenv ownership..."
    if [ "$OS_TYPE" = "Linux" ]; then
        sudo chown -R "$(whoami):$(whoami)" .devenv/ 2>/dev/null || true
    else
        # macOS/BSD doesn't always have group:user format
        sudo chown -R "$(whoami)" .devenv/ 2>/dev/null || true
    fi
fi

if [ -d "web/web/sites/default" ]; then
    echo "  - Fixing web/sites/default ownership..."
    if [ "$OS_TYPE" = "Linux" ]; then
        sudo chown -R "$(whoami):$(whoami)" web/web/sites/default/ 2>/dev/null || true
    else
        sudo chown -R "$(whoami)" web/web/sites/default/ 2>/dev/null || true
    fi
fi

echo "Step 5: Removing devenv state (project databases and config)..."
# This removes the project-specific .devenv directory containing databases
rm -rf .devenv

echo "Step 6: Cleaning Drupal installation..."
# Fix permissions first, then remove files
if [ -d "web/web/sites/default" ]; then
    echo "  - Fixing Drupal file permissions..."
    chmod -R u+w web/web/sites/default/ 2>/dev/null || true
fi

# Remove all Drupal installation files and state
echo "  - Removing Drupal files and installation state..."
rm -rf web/web/sites/default/files
rm -f web/web/sites/default/settings.php
rm -f web/web/sites/default/settings.local.php
rm -f web/web/sites/default/services.yml
rm -f web/web/sites/default/services.local.yml

# Remove additional Drupal state files that can cause "already installed" errors
rm -f web/web/sites/default/.htaccess
rm -f web/web/sites/default/salt.txt
rm -rf web/web/sites/default/config
rm -rf web/web/sites/default/sync
rm -rf web/web/sites/default/translations

# Remove any cached files or temporary installation state
rm -rf web/web/sites/default/php
rm -rf web/web/sites/default/css
rm -rf web/web/sites/default/js

echo "Step 7: Recreating required directories..."
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

# Final verification step
echo "Step 9: Verifying cleanup..."
if [ -d ".devenv" ]; then
    echo "  - Warning: .devenv directory still exists"
else
    echo "  - ✓ .devenv directory removed"
fi

if pgrep -f "postgres.*$(pwd)" >/dev/null 2>&1; then
    echo "  - Warning: PostgreSQL processes may still be running"
else
    echo "  - ✓ No PostgreSQL processes detected for this project"
fi

echo ""
echo "✅ Cleanup complete!"
