#!/bin/sh
set -eu

heartbeat=/tmp/lootwright-scheduler-heartbeat

while true; do
    php artisan schedule:run --no-interaction --no-ansi
    touch "$heartbeat"
    now="$(date +%s)"
    sleep "$((60 - now % 60))"
done
