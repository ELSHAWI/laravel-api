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

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configure Apache ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy ONLY composer files first
COPY composer.json composer.lock ./

# Install dependencies (without running scripts)
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copy the entire application
COPY . .

# Laravel production setup
RUN if [ ! -f ".env" ]; then \
        cp .env.example .env && \
        php artisan key:generate --force; \
    fi && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan storage:link && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan cache:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Configure Apache virtual host
RUN echo "<VirtualHost *:80>\n\
    ServerName ${SERVER_NAME}\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options Indexes FollowSymLinks\n\
    </Directory>\n\
    \n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf && \
    a2ensite 000-default.conf && \
    a2dissite 000-default

# API-specific .htaccess rules
RUN echo "Options -MultiViews\n\
RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteCond %{REQUEST_FILENAME} !-d\n\
RewriteRule ^ index.php [L]" > /var/www/html/public/.htaccess

# Environment variables
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APP_ENV=production \
    APP_DEBUG=false \
    SERVER_NAME=localhost

EXPOSE 80
CMD ["apache2-foreground"]