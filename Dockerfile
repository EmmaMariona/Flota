FROM php:8.2-apache

# Actualizar paquetes del sistema e instalar dependencias necesarias
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones de PHP necesarias (mysqli, pdo_mysql, gd, zip, fileinfo)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip fileinfo

# Habilitar módulos de Apache requeridos (rewrite, headers, alias)
RUN a2enmod rewrite headers alias

# Copiar configuraciones personalizadas de Apache y PHP
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Copiar script de inicio (entrypoint) y normalizar saltos de línea (Windows CRLF -> Unix LF)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Establecer directorio de trabajo
WORKDIR /var/www/html/flota

# Copiar el código fuente de la aplicación
COPY . /var/www/html/flota

# Crear carpetas de archivos adjuntos y configurar permisos para el usuario www-data
RUN mkdir -p /var/www/html/flota/backend/uploads/documentos \
    && mkdir -p /var/www/html/flota/backend/uploads/mantenimientos \
    && chown -R www-data:www-data /var/www/html/flota \
    && chmod -R 775 /var/www/html/flota/backend/uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
