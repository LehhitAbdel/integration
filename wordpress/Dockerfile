# official WordPress image 
FROM wordpress:latest

# Install dependencies 
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


COPY ./Client-api /var/www/html/wp-content/plugins/client-rest-api


# Install Composer dependencies for the plugin
RUN composer require php-amqplib/php-amqplib # from EersteZit
RUN composer install --working-dir=/var/www/html/wp-content/plugins/client-rest-api/

# Ensure correct permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html


EXPOSE 80
