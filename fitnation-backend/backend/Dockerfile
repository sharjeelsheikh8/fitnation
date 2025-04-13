# Use the official PHP image with Apache
FROM php:8.1-apache

# Install PHP extensions required for PostgreSQL and other dependencies
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the source code into the container
COPY ./src /var/www/html

# Expose port 80 to access the web app
EXPOSE 80