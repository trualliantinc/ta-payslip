# Dockerfile
FROM php:8.2-apache

# System deps for PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip \
    libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    zip \
    gd \
    mbstring \
    intl \
    curl \
    pdo \
    pdo_mysql \
    xml \
    dom \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1

# Workdir
WORKDIR /var/www/html

# Leverage build cache for vendor install
COPY composer.json composer.lock ./
RUN composer install -n --prefer-dist --no-dev --no-scripts

# App code
COPY . .

# If your project has post-install scripts / autoload dump:
RUN composer dump-autoload -o || true

# Apache on 8080
ENV APACHE_PORT=8080
RUN sed -ri -e 's!^Listen 80$!Listen ${APACHE_PORT}!g' /etc/apache2/ports.conf \
    && sed -ri -e 's!VirtualHost \*:80!VirtualHost \*:${APACHE_PORT}!g' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080
CMD ["apache2-foreground"]
