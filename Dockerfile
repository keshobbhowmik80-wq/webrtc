# Minimal PHP for Laravel without database
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# 1. Install ONLY curl (for composer) and zip extension
RUN apt-get update && apt-get install -y curl \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 3. Copy application files
COPY . .

# 4. Set Laravel permissions
RUN chmod -R 775 storage bootstrap/cache

# 5. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 6. Cache config (skip key generation since you have APP_KEY)
RUN php artisan config:cache

# 7. Expose port 8080
EXPOSE 8080

# 8. Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
