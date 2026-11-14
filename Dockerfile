FROM php:8.4-apache-trixie

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

RUN a2enmod rewrite headers

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY composer.json composer.lock ./
COPY app/ app/
COPY config/ config/
COPY database/ database/
COPY public/ public/
COPY routes/ routes/
COPY scripts/migrate.php scripts/migrate.php
COPY bootstrap.php ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php -r 'foreach (["curl", "mbstring", "pdo_sqlite", "sqlite3"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: $extension\n"); exit(1); } }' \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage

COPY docker/entrypoint.sh /usr/local/bin/challengechain-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/challengechain-entrypoint \
    && chmod +x /usr/local/bin/challengechain-entrypoint

EXPOSE 80

ENTRYPOINT ["challengechain-entrypoint"]
CMD ["apache2-foreground"]
