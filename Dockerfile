# Use PHP with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip sockets

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Copy application files
COPY . .

# 5. Create SQLite database file if doesn't exist
RUN touch database/database.sqlite \
    && chmod 777 database/database.sqlite

# 6. Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache \
    && chmod -R 775 database

# 7. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. Generate application key
RUN php artisan key:generate --force

# 9. Run migrations (if any)
# RUN php artisan migrate --force

# 10. Cache configuration
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# 11. Configure Apache
# Enable rewrite module
RUN a2enmod rewrite

# Change Apache port from 80 to 8080 (Render uses 8080)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Set document root to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# 12. Expose port 8080
EXPOSE 8080

# 13. Start Apache
CMD ["apache2-foreground"]
