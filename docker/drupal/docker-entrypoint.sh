#!/bin/bash
# =============================================================================
# Drupal Docker Entrypoint Script
# =============================================================================
#
# This script runs before PHP-FPM starts and ensures:
# 1. Database is available
# 2. Drupal settings include Docker environment configuration
# 3. File permissions are correct
#
# =============================================================================

set -e

# Colors for logging
log_info() {
    echo "[INFO] $1"
}

log_warn() {
    echo "[WARN] $1"
}

log_error() {
    echo "[ERROR] $1"
}

# Wait for database
wait_for_db() {
    local host="${DRUPAL_DB_HOST:-postgres}"
    local port="${DRUPAL_DB_PORT:-5432}"
    local max_attempts=30
    local attempt=1
    
    log_info "Waiting for database at ${host}:${port}..."
    
    while [ $attempt -le $max_attempts ]; do
        if pg_isready -h "$host" -p "$port" > /dev/null 2>&1; then
            log_info "Database is ready!"
            return 0
        fi
        log_info "Attempt $attempt/$max_attempts - Database not ready, waiting..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    log_error "Database not available after $max_attempts attempts"
    return 1
}

# Ensure settings.docker.php is included in settings.php
setup_settings() {
    local settings_file="/var/www/html/web/sites/default/settings.php"
    local docker_settings="/var/www/html/docker/drupal/settings.docker.php"
    local include_line='include $app_root . "/../docker/drupal/settings.docker.php";'
    
    if [ -f "$settings_file" ]; then
        # Check if include already exists
        if ! grep -q "settings.docker.php" "$settings_file"; then
            log_info "Adding Docker settings include to settings.php..."
            echo "" >> "$settings_file"
            echo "// Docker environment settings" >> "$settings_file"
            echo "if (file_exists(\$app_root . '/../docker/drupal/settings.docker.php')) {" >> "$settings_file"
            echo "  include \$app_root . '/../docker/drupal/settings.docker.php';" >> "$settings_file"
            echo "}" >> "$settings_file"
        else
            log_info "Docker settings include already present"
        fi
    else
        log_warn "settings.php not found - Drupal may need installation"
    fi
}

# Ensure file permissions
setup_permissions() {
    local files_dir="/var/www/html/web/sites/default/files"
    
    log_info "Setting up file permissions..."
    
    mkdir -p "$files_dir"
    chown www-data:www-data "$files_dir"
    chmod 755 "$files_dir"
    
    # Ensure private files directory exists
    mkdir -p "/var/www/html/web/sites/default/private"
    chown www-data:www-data "/var/www/html/web/sites/default/private"
    chmod 755 "/var/www/html/web/sites/default/private"
}

# Main entrypoint logic
main() {
    log_info "Starting Drupal entrypoint..."
    
    # Wait for database if configured
    if [ -n "$DRUPAL_DB_HOST" ]; then
        wait_for_db
    fi
    
    # Setup Drupal settings
    setup_settings
    
    # Setup permissions
    setup_permissions
    
    log_info "Entrypoint complete, starting PHP-FPM..."
    
    # Execute the main command (php-fpm)
    exec "$@"
}

# Run main function with all arguments
main "$@"
