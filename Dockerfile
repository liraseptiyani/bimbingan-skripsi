FROM php:8.2-apache

# Install PostgreSQL client libraries and PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache rewrite module (useful for routing)
RUN a2enmod rewrite

# Copy all application files to the web server root
COPY . /var/www/html/

# Create the uploads directory and set permissions so PHP can save uploaded files
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for the web traffic
EXPOSE 80
