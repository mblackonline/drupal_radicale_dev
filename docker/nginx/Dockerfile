# =============================================================================
# Drupal Nginx Image
# Multi-stage build - contains static files for serving
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Build dependencies (same as PHP-FPM for consistency)
# -----------------------------------------------------------------------------
FROM php:8.3-cli-alpine AS builder

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /build

# Copy composer files first for better layer caching
COPY ./web/composer.json ./web/composer.lock* ./

# Install dependencies (no dev, optimized autoloader)
# Ignore platform reqs since we only need static files, not PHP extensions
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Copy the rest of the application
COPY ./web .

# -----------------------------------------------------------------------------
# Stage 2: Production Nginx image with static files
# -----------------------------------------------------------------------------
FROM nginx:alpine AS production

# Copy only the web directory (document root) from builder
# This contains all static assets: CSS, JS, themes, libraries, etc.
COPY --from=builder /build/web /var/www/html/web

# Copy nginx configuration
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Create directories for uploaded files (will be mounted as volume)
RUN mkdir -p /var/www/html/web/sites/default/files \
    && chown -R nginx:nginx /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/nginx-health || exit 1

EXPOSE 80
