# =============================================================================
# Drupal PHP-FPM Image
# Multi-stage build for production deployment
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Build dependencies
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS builder

# Install build dependencies
RUN apk add --no-cache \
    git \
    unzip \
    # Required for some composer packages
    $PHPIZE_DEPS

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /build

# Copy composer files first for better layer caching
COPY ./web/composer.json ./web/composer.lock* ./

# Install dependencies (no dev, optimized autoloader)
# Ignore platform reqs since extensions are compiled in production stage
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Copy the rest of the application
COPY ./web .

# Run post-install scripts now that all files are present
RUN composer dump-autoload --optimize

# -----------------------------------------------------------------------------
# Stage 2: Production PHP-FPM image
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS production

# Install runtime dependencies only
RUN apk add --no-cache \
    # Required for GD
    freetype \
    libjpeg-turbo \
    libpng \
    libwebp \
    # Required for intl
    icu-libs \
    # Required for PostgreSQL
    postgresql-libs \
    postgresql-client \
    # Required for zip
    libzip \
    # Required for curl
    libcurl \
    # General utilities
    bash \
    # Required for healthcheck
    fcgi

# Install build deps, compile extensions, then remove build deps
RUN apk add --no-cache --virtual .build-deps \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        icu-dev \
        postgresql-dev \
        libzip-dev \
        curl-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        opcache \
        pdo \
        pdo_pgsql \
        zip \
        bcmath \
        exif \
        curl \
    && apk del .build-deps

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Custom PHP configuration for Drupal
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_vars = 3000'; \
    echo 'realpath_cache_size = 4096K'; \
    echo 'realpath_cache_ttl = 600'; \
} > "$PHP_INI_DIR/conf.d/drupal.ini"

# OPcache configuration for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
} > "$PHP_INI_DIR/conf.d/opcache.ini"

# PHP-FPM ping/status for healthcheck
RUN { \
    echo '[www]'; \
    echo 'ping.path = /ping'; \
    echo 'ping.response = pong'; \
} > /usr/local/etc/php-fpm.d/healthcheck.conf

# Set working directory
WORKDIR /var/www/html

# Copy built application from builder stage
COPY --from=builder /build /var/www/html

# Copy Docker-specific configuration
COPY ./docker/drupal/settings.docker.php /var/www/html/docker/drupal/settings.docker.php
COPY ./docker/drupal/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create required directories
RUN mkdir -p /var/www/html/web/sites/default/files \
    && mkdir -p /var/www/html/web/sites/default/private \
    && chmod 755 /var/www/html/web/sites/default/files \
    && chmod 755 /var/www/html/web/sites/default/private

# Always reset settings.php to default (prevents stale config from build environment)
RUN cp /var/www/html/web/sites/default/default.settings.php /var/www/html/web/sites/default/settings.php \
    && chmod 644 /var/www/html/web/sites/default/settings.php

# Set ownership
RUN chown -R www-data:www-data /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
