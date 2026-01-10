FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libonig-dev \
    libxml2-dev \
    zip unzip git curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring xml gd zip calendar bcmath \
    && rm -rf /var/lib/apt/lists/*

# Install Composer (must come BEFORE using composer)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Increase Composer process timeout
RUN composer config --global process-timeout 2000

# Increase PHP memory limit for Composer
RUN echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory-limit.ini

WORKDIR /var/www/html
