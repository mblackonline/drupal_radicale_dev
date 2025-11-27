# Drupal 11 with PHP 8.3 FPM
# Optimized for Docker Swarm deployment with PostgreSQL

FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    # Required for GD
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # Required for intl
    icu-dev \
    # Required for PostgreSQL
    postgresql-dev \
    postgresql-client \
    # Required for zip
    libzip-dev \
    # Required for curl (CalDAV requests)
    curl-dev \
    # General utilities
    git \
    unzip \
    bash \
    # Required for healthcheck
    fcgi

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
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
        curl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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

# Copy application code
COPY ./web /var/www/html

# Copy Docker-specific files
COPY ./docker/drupal/settings.docker.php /var/www/html/docker/drupal/settings.docker.php
COPY ./docker/drupal/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create required directories with correct permissions
RUN mkdir -p /var/www/html/web/sites/default/files \
    && mkdir -p /var/www/html/web/sites/default/private \
    && chmod 755 /var/www/html/web/sites/default/files \
    && chmod 755 /var/www/html/web/sites/default/private

# Create settings.php from default if not exists
RUN if [ -f /var/www/html/web/sites/default/default.settings.php ] && [ ! -f /var/www/html/web/sites/default/settings.php ]; then \
        cp /var/www/html/web/sites/default/default.settings.php /var/www/html/web/sites/default/settings.php; \
    fi \
    && chmod 644 /var/www/html/web/sites/default/settings.php 2>/dev/null || true

# Set ownership for www-data (PHP-FPM user in Alpine)
RUN chown -R www-data:www-data /var/www/html

# PHP-FPM healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
