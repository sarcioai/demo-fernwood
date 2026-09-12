#!/bin/sh
# Serve the baked install under SARCIO_BASE_PATH by linking that path in the
# document root to it — no rewriting, so every URL WordPress generates is real.
set -e
base="${SARCIO_BASE_PATH%/}"
docroot=/var/www/wp
if [ -n "$base" ]; then
  mkdir -p "/var/www/html$(dirname "$base")"
  ln -sfn /var/www/wp "/var/www/html$base"
  docroot=/var/www/html
fi
exec php -S "0.0.0.0:${PORT:-4013}" -t "$docroot" /app/router.php
