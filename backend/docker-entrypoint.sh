#!/bin/sh
set -eu

if [ "$1" = "apache2-foreground" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        if [ "${APP_ENV:-production}" != "local" ]; then
            echo "APP_KEY must be configured for deployment." >&2
            exit 1
        fi

        # Persist a randomly generated development key across container rebuilds.
        key_file=storage/app/.local-app-key
        if [ ! -s "$key_file" ]; then
            umask 077
            printf 'base64:%s\n' "$(php -r 'echo base64_encode(random_bytes(32));')" > "$key_file"
            umask 022
        fi
        APP_KEY="$(cat "$key_file")"
        export APP_KEY
    fi

    php artisan config:clear
    php artisan migrate --force

    if [ "${APP_ENV:-production}" = "production" ]; then
        php artisan config:cache
    fi

    chown -R www-data:www-data storage bootstrap/cache
fi

exec docker-php-entrypoint "$@"
