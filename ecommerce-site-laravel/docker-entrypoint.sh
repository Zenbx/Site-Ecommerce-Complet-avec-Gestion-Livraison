#!/bin/bash
set -e

echo "Running Composer scripts (package discovery)..."
composer run-script post-autoload-dump || true

echo "Generating application key if needed..."
php artisan key:generate --force || true

echo "Running migrations..."
php artisan migrate --force

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'
