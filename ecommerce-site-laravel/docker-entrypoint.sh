#!/bin/bash
set -e

echo "Creating .env file from environment variables..."
# Create .env file if it doesn't exist (Render uses env vars, but Laravel needs .env)
if [ ! -f .env ]; then
    touch .env
    echo "APP_NAME=\"${APP_NAME}\"" >> .env
    echo "APP_ENV=${APP_ENV}" >> .env
    echo "APP_KEY=${APP_KEY}" >> .env
    echo "APP_DEBUG=${APP_DEBUG}" >> .env
    echo "APP_URL=${APP_URL}" >> .env
    echo "" >> .env
    echo "DB_CONNECTION=${DB_CONNECTION}" >> .env
    echo "DB_HOST=${DB_HOST}" >> .env
    echo "DB_PORT=${DB_PORT}" >> .env
    echo "DB_DATABASE=${DB_DATABASE}" >> .env
    echo "DB_USERNAME=${DB_USERNAME}" >> .env
    echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
    echo "" >> .env
    echo "BROADCAST_CONNECTION=${BROADCAST_CONNECTION}" >> .env
    echo "PUSHER_APP_ID=${PUSHER_APP_ID}" >> .env
    echo "PUSHER_APP_KEY=${PUSHER_APP_KEY}" >> .env
    echo "PUSHER_APP_SECRET=${PUSHER_APP_SECRET}" >> .env
    echo "PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER}" >> .env
    echo "PUSHER_SCHEME=${PUSHER_SCHEME}" >> .env
    echo "PUSHER_PORT=${PUSHER_PORT}" >> .env
fi

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
