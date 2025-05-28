# Use PHP 8.2 with Apache and PostgreSQL support
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libcurl4-gnutls-dev libicu-dev libmagic-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql zip gd bcmath mbstring exif opcache intl xml curl dom fileinfo \
    && a2enmod rewrite

# Configure Apache ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer (with safety checks)
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer \
    --version=2.6.6 && \
    chmod +x /usr/local/bin/composer

# Copy ONLY composer files first
COPY composer.json composer.lock ./

# Install dependencies (skip all scripts)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction

# Copy the entire application
COPY . .

# Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Generate optimized autoload (without triggering scripts)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-scripts

# Configure Apache virtual host (FIXED VERSION)
RUN mkdir -p /etc/apache2/sites-available/ && \
    mkdir -p /etc/apache2/sites-enabled/ && \
    echo "<VirtualHost *:80>\n\
    ServerName localhost\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options Indexes FollowSymLinks\n\
    </Directory>\n\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf && \
    ln -s /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Now safe to run artisan commands
RUN if [ ! -f ".env" ]; then \
        cp .env.example .env && \
        php artisan key:generate --force; \
    fi && \
    php artisan storage:link && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan cache:clear

# Production optimizations (run last)
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 80
CMD ["apache2-foreground"]