# Use official PHP image with Apache for PHP 8.1
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Fix repository issues and install base dependencies first
RUN echo "Acquire::Check-Valid-Until \"false\";\nAcquire::Check-Date \"false\";" > /etc/apt/apt.conf.d/10no--check-valid-until && \
    apt-get update -y && \
    apt-get install -y --no-install-recommends \
    ca-certificates \
    gnupg \
    software-properties-common

# Install system dependencies in batches
RUN apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-gnutls-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Install libmagic separately with clean step
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libmagic-dev && \
    rm -rf /var/lib/apt/lists/*

# Configure GD before installation
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions with limited parallelism (-j2)
# fileinfo installed separately first
RUN docker-php-ext-install -j2 fileinfo
RUN docker-php-ext-install -j2 \
    pdo \
    pdo_mysql \
    zip \
    gd \
    bcmath \
    mbstring \
    exif \
    opcache \
    intl \
    xml \
    curl \
    dom

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Install dependencies (production only)
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Generate application key (fallback)
RUN php artisan key:generate || true

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default.conf && a2ensite 000-default.conf

# Environment variables
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    WEBROOT=/var/www/html/public

EXPOSE 80
CMD ["apache2-foreground"]