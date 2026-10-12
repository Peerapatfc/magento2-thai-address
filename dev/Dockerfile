# Throw-away PHP 8.3 image with everything Magento 2.4.8 needs.
FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip curl default-mysql-client \
      libicu-dev libxml2-dev libxslt1-dev libzip-dev libfreetype6-dev libjpeg62-turbo-dev \
      libpng-dev libsodium-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath ftp gd intl opcache pdo_mysql soap sockets sodium xsl zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN printf 'memory_limit=4G\nrealpath_cache_size=10M\n' > /usr/local/etc/php/conf.d/zz-magento.ini

WORKDIR /var/www/html
