# Use official PHP image with Apache for PHP 8.1
FROM php:8.1-apache

# Set working directory for the application
WORKDIR /var/www/html

# Install system dependencies and PHP extensions required by Laravel and its packages.
# Combining these into a single RUN instruction and cleaning up reduces image size.
RUN apt-get update && apt-get install -y \
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
    # Add other system libraries here if your project needs them, e.g., libpq-dev for PostgreSQL
    && rm -rf /var/lib/apt/lists/* \
    \
    # Configure and install PHP extensions.
    # -j$(nproc) speeds up compilation by using all available CPU cores.
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
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
        fileinfo \
    \
    # Clean up PECL cache and remove xdebug if it's installed (for production performance)
    && pecl clear-cache \
    && rm -rf /tmp/pear ~/.pearrc \
    && [ -f /usr/local/etc/php/conf.d/xdebug.ini ] && rm /usr/local/etc/php/conf.d/xdebug.ini

# Enable Apache mod_rewrite, essential for Laravel's URL routing
RUN a2enmod rewrite

# Copy composer.json and composer.lock first to leverage Docker's build cache.
# If these files don't change, Docker can use a cached layer for `composer install`.
COPY composer.json composer.lock ./

# Install Composer globally in the container
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies from composer.lock
# --no-dev: Skips development dependencies
# --optimize-autoloader: Generates a faster class map
# COMPOSER_ALLOW_SUPERUSER is often needed during build if running as root
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application files
# This step should happen AFTER composer install so that if only code changes (not composer files),
# composer install step can be cached.
COPY . .

# Generate Laravel app key
# '|| true' allows the build to continue even if key:generate fails (e.g., no .env copied).
# It's best to set APP_KEY as an environment variable in Render.com for production.
RUN php artisan key:generate || true

# Set appropriate permissions for Laravel's storage and cache directories.
# www-data is the user Apache runs as in this base image.
RUN chown -R www-data:www-data storage bootstrap/cache

# Configure Apache to serve from Laravel's public directory
# The 'docker/apache.conf' file must exist in your project root relative to the Dockerfile.
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
# Disable the default Apache site and enable your custom Laravel site config
RUN a2dissite 000-default.conf
RUN a2ensite 000-default.conf

# Expose port 80, which Apache listens on
EXPOSE 80

# Laravel and application specific environment variables (optional, but good defaults)
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
# Ensure the WEBROOT points to the public folder for web server
ENV WEBROOT /var/www/html/public

# Start Apache in the foreground. This is the main process the container will run.
CMD ["apache2-foreground"]