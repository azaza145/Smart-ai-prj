FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    python3 \
    python3-pip \
    python3-venv \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli zip gd \
    && a2enmod rewrite headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Python dependencies (install in system for simplicity so PHP can call them)
RUN pip3 install --break-system-packages pandas scikit-learn mysql-connector-python pdfminer.six

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Suppress AH00558: set ServerName so Apache does not use the container IP
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername

WORKDIR /var/www/html

# Entrypoint: ensure composer deps (dompdf, phpword) are installed when container starts
COPY docker-entrypoint.sh /usr/local/bin/recruteia-entrypoint.sh
RUN chmod +x /usr/local/bin/recruteia-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/recruteia-entrypoint.sh"]
CMD ["apache2-foreground"]
