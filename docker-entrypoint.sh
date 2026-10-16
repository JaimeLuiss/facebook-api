#!/bin/sh
set -e

PORT="${PORT:-10000}"
case "$PORT" in
    ''|*[!0-9]*)
        echo "PORT debe ser un numero de puerto valido." >&2
        exit 1
        ;;
esac

echo "=== Limpiando y optimizando caches de Laravel ==="
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
else
    php artisan config:clear
fi
php artisan route:cache
php artisan view:cache

echo "=== Ejecutando migraciones de PostgreSQL ==="
php artisan migrate --force

echo "=== Creando enlace simbolico de storage ==="
php artisan storage:link || true

echo "=== Configurando Nginx en el puerto ${PORT} ==="
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

echo "=== Iniciando PHP-FPM y Nginx ==="
exec supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
