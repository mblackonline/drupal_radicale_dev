# =============================================================================
# Custom Radicale Image with auto-htpasswd generation
# =============================================================================

FROM tomsquest/docker-radicale:latest

# Install apache2-utils for htpasswd command
USER root
RUN apk add --no-cache apache2-utils

# Copy custom entrypoint
COPY docker/radicale/docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy default config
COPY docker/radicale/config /config/config

# Ensure directories exist with correct permissions
RUN mkdir -p /data && chown -R radicale:radicale /data /config

USER radicale

ENTRYPOINT ["/entrypoint.sh"]
