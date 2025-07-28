#!/bin/bash
# Setup script for fresh Git clones
# Ensures all required directories and permissions are in place

echo "=== Drupal + Radicale Project Setup ==="
echo "Setting up required directories and permissions..."

# Create required directories if they don't exist
echo "Creating required directories..."
mkdir -p web/web/sites/default/files
mkdir -p web/web/sites/default/private

# Fix ownership if needed (in case of previous sudo usage)
if [ ! -w web/web/sites/default/files ] || [ ! -w web/web/sites/default ]; then
    echo "Fixing ownership of Drupal directories..."
    sudo chown -R $(whoami):$(whoami) web/web/sites/default/ 2>/dev/null || true
fi

# Set proper permissions
echo "Setting proper permissions..."
chmod 755 web/web/sites/default/files
chmod 755 web/web/sites/default/private

# Ensure settings.php exists and is writable for installation
if [ -f web/web/sites/default/default.settings.php ]; then
    if [ ! -f web/web/sites/default/settings.php ]; then
        echo "Creating settings.php from default..."
        cp web/web/sites/default/default.settings.php web/web/sites/default/settings.php
    fi
    chmod 666 web/web/sites/default/settings.php
else
    echo "Warning: default.settings.php not found. Drupal core may not be installed yet."
    echo "Run 'cd web && composer install && cd ..' first."
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "  1. Run: devenv shell"
echo "  2. Run: cd web && composer install && cd .."
echo "  3. Run: devenv up -d"
echo "  4. Visit: http://127.0.0.1:8000"
echo "  5. Select 'Radicale Calendar Starter' installation profile"
