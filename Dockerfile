# Fernwood Journal, built from THIS repo alone. The Sarcio plugin is a Composer
# dependency; WordPress core, the SQLite drop-in, this repo's theme and its
# mu-plugin are provisioned at BUILD time so container start is instant and offline.
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
# tar/unzip for the core tarball; pdo_sqlite for the drop-in. sqlite-dev is a
# build-only dep — drop it again and keep just the runtime library.
RUN apk add --no-cache tar unzip sqlite-libs \
  && apk add --no-cache --virtual .build-deps sqlite-dev \
  && docker-php-ext-install pdo_sqlite \
  && apk del .build-deps

WORKDIR /app
COPY --from=deps /app/vendor ./vendor
COPY setup.php router.php docker-entrypoint.sh ./
COPY wp-content ./wp-content
RUN php /app/setup.php --dir=/var/www/wp && chown -R www-data:www-data /var/www/wp

# The built-in server handles one request at a time unless told otherwise; the
# widget, REST calls and assets arrive in parallel.
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 4013
# PORT / SARCIO_* come from the environment; wp-config.php reads them per request.
CMD ["/app/docker-entrypoint.sh"]
