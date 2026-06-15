#!/bin/sh
set -e

echo "=== Laravel Startup ==="

# Esperar a que la base de datos esté disponible
if [ ! -z "$DB_HOST" ]; then
    echo "Esperando PostgreSQL en $DB_HOST:$DB_PORT..."
    while ! nc -z $DB_HOST $DB_PORT; do
        sleep 1
    done
    echo "PostgreSQL listo"
fi

# Crear directorios si no existen
mkdir -p /var/www/storage/framework/{sessions,views,cache}
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/bootstrap/cache

# Permisos
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Si no existe .env, copiar el example
if [ ! -f /var/www/.env ]; then
    cp /var/www/.env.example /var/www/.env
fi

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generando APP_KEY..."
    cd /var/www && php artisan key:generate --no-interaction --force
fi

# Cache de configuración y rutas en producción
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizando para producción..."
    cd /var/www
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
    php artisan filament:upgrade --no-interaction
fi

# Migraciones (con opción de skip)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Ejecutando migraciones..."
    cd /var/www && php artisan migrate --force --no-interaction
fi

# Storage link
if [ ! -L /var/www/public/storage ]; then
    cd /var/www && php artisan storage:link --no-interaction
fi

echo "=== Iniciando servicios ==="
# Iniciar supervisord
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
