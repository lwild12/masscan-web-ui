FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
        libpq-dev \
        masscan \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/masscan.ini

# Copy application source
COPY . /var/www/html/

# Remove sensitive / dev-only files from the image
RUN rm -f /var/www/html/.env \
          /var/www/html/.env.example \
          /var/www/html/docker-compose.override.yml

# Correct ownership
RUN chown -R www-data:www-data /var/www/html

# Create imports directory with correct permissions
RUN mkdir -p /var/www/html/imports \
    && chown -R www-data:www-data /var/www/html/imports

WORKDIR /var/www/html
