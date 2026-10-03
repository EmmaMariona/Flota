#!/bin/bash
set -e

# Crear directorios de uploads si no existen
mkdir -p /var/www/html/flota/backend/uploads/documentos
mkdir -p /var/www/html/flota/backend/uploads/mantenimientos

# Ajustar permisos para que el servidor web Apache pueda escribir adjuntos
chown -R www-data:www-data /var/www/html/flota/backend/uploads
chmod -R 775 /var/www/html/flota/backend/uploads

# Ejecutar el comando original del contenedor (apache2-foreground)
exec "$@"
