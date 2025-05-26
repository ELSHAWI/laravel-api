# Use official PHP image with Apache for PHP 8.1
FROM php:8.1-apache

# Set working directory for the application
WORKDIR /var/www/html

# Fix potential certificate and repository issues
RUN echo "Acquire::Check-Valid-Until \"false\";\nAcquire::Check-Date \"false\";" > /etc/apt/apt.conf.d/10no--check-valid-until

# Install system dependencies in separate steps for better caching and error handling
RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install -y --no-install-recommends \
    ca-certificates \
    gnupg \
    software-properties-common

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
    libmagic-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions in separate steps
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
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
    dom \
    fileinfo

# Clean up PECL cache and remove xdebug if it's installed
# RUN pecl clear-cache \
#     && rm -rf /tmp/pear ~/.pearrc \
#     && [ -f /usr/local/etc/php/conf.d/xdebug.ini ] && rm /usr/local/etc/php/conf.d/xdebug.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (using --no-dev for production)
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy the rest of the application files
COPY . .

# Generate Laravel app key (fallback if not set in environment)
RUN php artisan key:generate || true

# Set appropriate permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default.conf && a2ensite 000-default.conf

# Expose port 80
EXPOSE 80

# Environment variables
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV WEBROOT /var/www/html/public

# Start Apache
CMD ["apache2-foreground"]