#!/bin/sh
set -eu

if [ "${ALLOW_ISOLATED_RESTORE:-}" != "yes" ]; then
    echo 'Refused: set ALLOW_ISOLATED_RESTORE=yes only inside an approved isolated restore exercise.' >&2
    exit 64
fi

: "${BACKUP_FILE:?Set BACKUP_FILE to an encrypted-backup working copy already decrypted in the isolated environment}"
: "${RESTORE_PGSERVICE:?Set RESTORE_PGSERVICE to a protected PostgreSQL service-file entry}"
: "${RESTORE_DB_HOST:?Set RESTORE_DB_HOST for the isolated target}"
: "${RESTORE_DB_PORT:=5432}"
: "${RESTORE_DB_NAME:?Set RESTORE_DB_NAME; it must end with _restore_verify}"
: "${RESTORE_DB_USER:?Set RESTORE_DB_USER for the exercise role}"
: "${RESTORE_DB_PASSWORD:?Inject RESTORE_DB_PASSWORD through the exercise secret manager}"
: "${RESTORE_DB_SSLROOTCERT:?Set RESTORE_DB_SSLROOTCERT to the isolated target CA file}"
: "${RESTORE_APP_KEY:?Set RESTORE_APP_KEY through the exercise secret manager}"

if [ ! -f "$BACKUP_FILE" ]; then
    echo 'Refused: BACKUP_FILE is not a regular file.' >&2
    exit 66
fi

database_name="$(psql "service=$RESTORE_PGSERVICE" --no-psqlrc --tuples-only --no-align --command 'select current_database()')"
case "$database_name" in
    *_restore_verify) ;;
    *)
        echo 'Refused: target database name must end with _restore_verify.' >&2
        exit 65
        ;;
esac
if [ "$database_name" != "$RESTORE_DB_NAME" ]; then
    echo 'Refused: RESTORE_DB_NAME does not match the service target.' >&2
    exit 65
fi

pg_restore --list "$BACKUP_FILE" >/dev/null
pg_restore \
    --clean \
    --if-exists \
    --exit-on-error \
    --no-owner \
    --no-privileges \
    --dbname "service=$RESTORE_PGSERVICE" \
    "$BACKUP_FILE"

APP_ENV=testing \
APP_DEBUG=false \
APP_KEY="$RESTORE_APP_KEY" \
DB_CONNECTION=pgsql \
DB_HOST="$RESTORE_DB_HOST" \
DB_PORT="$RESTORE_DB_PORT" \
DB_DATABASE="$RESTORE_DB_NAME" \
DB_USERNAME="$RESTORE_DB_USER" \
DB_PASSWORD="$RESTORE_DB_PASSWORD" \
DB_SSLMODE=verify-full \
DB_SSLROOTCERT="$RESTORE_DB_SSLROOTCERT" \
CACHE_STORE=array \
QUEUE_CONNECTION=sync \
SESSION_DRIVER=array \
php artisan migrate --force --no-interaction

APP_ENV=testing APP_KEY="$RESTORE_APP_KEY" DB_CONNECTION=pgsql DB_HOST="$RESTORE_DB_HOST" DB_PORT="$RESTORE_DB_PORT" DB_DATABASE="$RESTORE_DB_NAME" DB_USERNAME="$RESTORE_DB_USER" DB_PASSWORD="$RESTORE_DB_PASSWORD" DB_SSLMODE=verify-full DB_SSLROOTCERT="$RESTORE_DB_SSLROOTCERT" \
CACHE_STORE=array QUEUE_CONNECTION=sync SESSION_DRIVER=array \
php artisan security:prune-retained-data --no-interaction

APP_ENV=testing APP_KEY="$RESTORE_APP_KEY" DB_CONNECTION=pgsql DB_HOST="$RESTORE_DB_HOST" DB_PORT="$RESTORE_DB_PORT" DB_DATABASE="$RESTORE_DB_NAME" DB_USERNAME="$RESTORE_DB_USER" DB_PASSWORD="$RESTORE_DB_PASSWORD" DB_SSLMODE=verify-full DB_SSLROOTCERT="$RESTORE_DB_SSLROOTCERT" \
CACHE_STORE=array QUEUE_CONNECTION=sync SESSION_DRIVER=array \
php artisan analysis:prune-artifacts --no-interaction

APP_ENV=testing APP_KEY="$RESTORE_APP_KEY" DB_CONNECTION=pgsql DB_HOST="$RESTORE_DB_HOST" DB_PORT="$RESTORE_DB_PORT" DB_DATABASE="$RESTORE_DB_NAME" DB_USERNAME="$RESTORE_DB_USER" DB_PASSWORD="$RESTORE_DB_PASSWORD" DB_SSLMODE=verify-full DB_SSLROOTCERT="$RESTORE_DB_SSLROOTCERT" \
CACHE_STORE=array QUEUE_CONNECTION=sync SESSION_DRIVER=array \
php artisan pob:prune-imports --no-interaction

psql "service=$RESTORE_PGSERVICE" --no-psqlrc --set=ON_ERROR_STOP=1 --command \
    "select count(*) as applied_migrations from migrations;"

echo 'Restore verification completed. Keep the target isolated until deletion reconciliation and operator review are recorded.'
