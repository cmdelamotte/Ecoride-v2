FROM php:8.3-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libssl-dev \
    pkg-config \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mysqli zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Set working directory
WORKDIR /var/www/html

# Copier le reste de l'application
COPY . .

# Changer le propriétaire des fichiers pour correspondre à l'utilisateur de Nginx/PHP-FPM
RUN chown -R www-data:www-data /var/www/html