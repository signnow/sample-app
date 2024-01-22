#!/usr/bin/env bash

chown -R www-data:www-data /app/storage
chmod -R g+s /app/storage
chown -R www-data:www-data /app/bootstrap
chmod -R g+s /app/bootstrap

#rm /app/storage/framework/views/* \
#   /app/storage/framework/cache/* \
#   /app/storage/framework/sessions/*

exec "$@"
