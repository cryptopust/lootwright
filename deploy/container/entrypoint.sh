#!/bin/sh
set -eu

umask 077

mkdir -p \
    bootstrap/cache \
    storage/app/private/analysis-artifacts \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views

php artisan deploy:check-config --no-ansi
php artisan config:cache --no-ansi

case "${1:-}" in
    web)
        exec /app/deploy/container/start-web.sh
        ;;
    queue)
        exec php artisan horizon
        ;;
    scheduler)
        exec /app/deploy/container/start-scheduler.sh
        ;;
    migrate)
        exec php artisan migrate --force --isolated --no-interaction
        ;;
    *)
        echo 'Unknown container role. Expected web, queue, scheduler, or migrate.' >&2
        exit 64
        ;;
esac
