# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libcurl4-gnutls-dev libicu-dev libmagic-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql zip gd bcmath mbstring exif opcache intl xml curl dom fileinfo \
    && a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Copy ONLY composer files first
COPY composer.json composer.lock ./

# Install dependencies (without running scripts)
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copy the entire application
COPY . .

# Run Laravel optimizations
RUN composer dump-autoload --optimize && \
    php artisan package:discover --ansi && \
    php artisan optimize:clear && \
    chown -R www-data:www-data storage bootstrap/cache

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Environment variables
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APP_ENV=production \
    APP_DEBUG=false

# Expose port and start Apache
EXPOSE 80
CMD ["apache2-foreground"]