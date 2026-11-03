#!/bin/sh
set -eu

# Refresh only public release files, leaving user-uploaded storage untouched.
# The app image bakes a clean public tree before the runtime volume is mounted.
mkdir -p /var/www/html/public
cp -a /opt/app-public/. /var/www/html/public/
mkdir -p /var/www/html/storage/app/public
if [ ! -e /var/www/html/public/storage ]; then
  ln -s /var/www/html/storage/app/public /var/www/html/public/storage
fi

exec "$@"
