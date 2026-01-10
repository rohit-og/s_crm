FROM php:8.2-fpm

# Install system deps
RUN apt-get update && apt-get install -y \
    build-essential \
    libonig-dev \
    libxml2-dev \
    zip unzip git curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Configure GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    gd \
    zip \
    calendar

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
