# Fernwood Nursery, built from THIS repo alone. The Sarcio plugin is a Composer
# dependency; WordPress core, the SQLite drop-in, WooCommerce, this repo's theme
# and mu-plugins, and the stocked store are provisioned at BUILD time so
# container start is instant and offline.
#
# sarcio/wordpress is a private VCS package, so the build needs a GitHub token:
#   docker build --secret id=composer_auth,src=auth.json -t demo-fernwood .
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json ./
RUN --mount=type=secret,id=composer_auth,target=/tmp/auth.json \
    COMPOSER_AUTH="$(cat /tmp/auth.json 2>/dev/null || echo '{}')" \
    composer install --no-dev --no-interaction --no-progress

FROM php:8.4-cli-alpine
# tar/unzip for the downloads; pdo_sqlite for the drop-in; gd for the product
# thumbnails WooCommerce generates. The -dev packages are build-only.
RUN apk add --no-cache tar unzip sqlite-libs libpng libjpeg-turbo libwebp freetype \
  && apk add --no-cache --virtual .build-deps sqlite-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
  && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
  && docker-php-ext-install pdo_sqlite gd \
  && apk del .build-deps

WORKDIR /app
COPY --from=deps /app/vendor ./vendor
COPY setup.php provision.php router.php connect.php docker-entrypoint.sh ./
COPY wp-content ./wp-content
COPY assets ./assets
# The web root is /var/www/wp; the SQLite database (the drop-in's DB_DIR) and the
# download cache stay OUTSIDE it, because PHP's built-in server ignores the
# drop-in's .htaccess and would serve them. The cache is build-only.
RUN php -d memory_limit=512M /app/setup.php --dir=/var/www/wp --data-dir=/var/lib/fernwood --cache-dir=/tmp/fernwood-cache \
  && rm -rf /tmp/fernwood-cache \
  && chown -R www-data:www-data /var/www/wp /var/lib/fernwood

# The built-in server handles one request at a time unless told otherwise; the
# widget, REST calls and assets arrive in parallel.
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 4013
# PORT / SARCIO_* come from the environment; wp-config.php reads them per request.
CMD ["/app/docker-entrypoint.sh"]
