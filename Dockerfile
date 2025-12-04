# Use PHP with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    && docker-php-ext-install zip pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Copy application files
COPY . .

# 4. Ensure .env exists
RUN if [ ! -f ".env" ]; then cp .env.example .env; fi

# 5. Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# 6. Create database directory
RUN mkdir -p database && chmod 775 database

# 7. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. Generate application key (with error handling)
RUN php artisan key:generate --force --no-interaction || \
    php artisan key:generate --force || \
    echo "Key generation may have failed, but continuing..."

# 9. Cache configuration
RUN php artisan config:cache || true

# 10. Configure Apache for port 8080
RUN a2enmod rewrite \
    && sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf \
    && echo "Listen 8080" >> /etc/apache2/ports.conf

# Set document root to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# 11. Expose port 8080
EXPOSE 8080

# 12. Start Apache
CMD ["apache2-foreground"]
