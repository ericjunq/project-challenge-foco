FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && sed -i 's/\r$//' docker/start.sh

EXPOSE 8000

CMD ["sh", "docker/start.sh"]