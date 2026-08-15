#!/usr/bin/env bash
set -euo pipefail

php-fpm --nodaemonize &
fpm_pid=$!
nginx -g 'daemon off;' &
nginx_pid=$!

terminate() {
    kill -TERM "$fpm_pid" "$nginx_pid" 2>/dev/null || true
}

trap terminate TERM INT

set +e
wait -n "$fpm_pid" "$nginx_pid"
status=$?
set -e

terminate
wait "$fpm_pid" "$nginx_pid" 2>/dev/null || true
exit "$status"
