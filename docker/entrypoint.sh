#!/bin/sh
set -e
# In prod, /app/public is a volume shared with nginx. Populate it from the image copy.
if [ -d /app/public_origin ]; then
    cp -ra /app/public_origin/. /app/public/
fi
# Run the given command, or php-fpm if none (e.g. docker compose run app php bin/console ... must work)
exec "${@:-php-fpm}"
