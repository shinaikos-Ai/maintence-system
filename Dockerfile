FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libpq-dev libzip-dev libonig-dev libpng-dev libxml2-dev unzip git \
    && docker-php-ext-install intl pdo_pgsql mbstring zip gd bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/maintenance.ini
RUN composer dump-autoload --optimize --no-dev && php artisan filament:assets \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
