#!/usr/bin/env bash

# This runs automatically on every deploy/restart (RUN_SCRIPTS=1 in the Dockerfile).
# It caches config/routes/views for performance, then applies any new migrations.

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link || true
