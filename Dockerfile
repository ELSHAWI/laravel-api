# Use official PHP image with Apache
FROM php:8.1-apache

# Set working directory for the application
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
# Add common Laravel dependencies like bcmath, gd (if used), mbstring, xml, etc.
# Check your specific Laravel project needs
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd bcmath mbstring exif opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy app files
# Copy composer.json and composer.lock first to leverage Docker cache
COPY composer.json composer.lock ./

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
# ENV COMPOSER_ALLOW_SUPERUSER is generally not needed if permissions are handled well
# but can be kept for initial debugging on root.
# Add if you need to run as root: ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application code
COPY . .

# Generate Laravel app key
# Ensure .env is available during the build if APP_KEY is not set via ENV
# or you need other .env variables for artisan commands.
# If .env is not copied, you might get a warning about missing APP_KEY,
# but it can be set later via Render's environment variables.
RUN php artisan key:generate || true # Add || true to allow build to pass if key:generate fails due to missing .env/key

# Set permissions for storage and cache
# Important for Apache (www-data user) to write to these directories
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port 80
EXPOSE 80

# Laravel config - These are usually set via Render's environment variables,
# but can be defaulted here.
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV WEBROOT /var/www/html/public # If your public folder is named differently

# Configure Apache to serve from public directory
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default.conf
RUN a2ensite 000-default.conf

# Start Apache in the foreground
CMD ["apache2-foreground"]