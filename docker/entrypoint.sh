#!/usr/bin/env bash

cp .env.example .env
composer install -n
php bin/console doctrine:migration:migrate --no-interaction

exec "$@"