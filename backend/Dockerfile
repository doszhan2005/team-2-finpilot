FROM php:8.5-apache

ARG INSTALL_DEV=true
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite headers \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' '<Directory /var/www/html/public>' '    Options -Indexes +FollowSymLinks' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/finpilot.conf \
    && a2enconf finpilot \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    else \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    fi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/finpilot-entrypoint
RUN chmod +x /usr/local/bin/finpilot-entrypoint

EXPOSE 80

ENTRYPOINT ["finpilot-entrypoint"]
CMD ["apache2-foreground"]
