#!/bin/bash
set -e

# Default to port 80 if PORT environment variable is not set by hosting provider
TARGET_PORT="${PORT:-80}"

# Configure Apache to listen on TARGET_PORT
sed -i "s/Listen [0-9]*/Listen ${TARGET_PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${TARGET_PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Execute passed command (apache2-foreground)
exec "$@"
