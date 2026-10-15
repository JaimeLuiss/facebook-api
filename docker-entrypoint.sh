#!/bin/sh
set -e

echo "=== Limpiando y optimizando caches de Laravel ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Ejecutando migraciones de PostgreSQL ==="
php artisan migrate --force

echo "=== Creando enlace simbolico de storage ==="
php artisan storage:link || true

echo "=== Iniciando Laravel en el puerto ${PORT:-10000} ==="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
