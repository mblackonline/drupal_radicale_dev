#!/bin/bash
# =============================================================================
# Drupal Docker Entrypoint Script
# =============================================================================
#
# This script runs before PHP-FPM starts and ensures:
# 1. Database is available
# 2. Settings.php is reset if database is empty (fresh install)
# 3. Drupal settings include Docker environment configuration
# 4. File permissions are correct
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

# Check if database is empty (no Drupal tables)
is_db_empty() {
    local host="${DRUPAL_DB_HOST:-postgres}"
    local port="${DRUPAL_DB_PORT:-5432}"
    local db="${DRUPAL_DB_NAME:-drupal}"
    local user="${DRUPAL_DB_USER:-drupal}"
    
    # Check if any tables exist in public schema
    local table_count=$(PGPASSWORD="${DRUPAL_DB_PASSWORD}" psql -h "$host" -p "$port" -U "$user" -d "$db" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ')
    
    if [ "$table_count" = "0" ] || [ -z "$table_count" ]; then
        return 0  # Empty
    else
        return 1  # Has tables
    fi
}

# Reset settings.php to default for fresh install
reset_settings_for_fresh_install() {
    local settings_file="/var/www/html/web/sites/default/settings.php"
    local default_settings="/var/www/html/web/sites/default/default.settings.php"
    
    if is_db_empty; then
        log_info "Database is empty - checking if settings.php needs reset..."
        
        # Check if settings.php has hardcoded database config (sign of previous install)
        if [ -f "$settings_file" ] && grep -q "\$databases\['default'\]\['default'\] = array" "$settings_file"; then
            log_info "Found stale database config in settings.php - resetting for fresh install..."
            cp "$default_settings" "$settings_file"
            chown www-data:www-data "$settings_file"
            chmod 644 "$settings_file"
            log_info "settings.php reset to default"
        fi
    else
        log_info "Database has existing tables - keeping current settings.php"
    fi
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
        reset_settings_for_fresh_install
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
