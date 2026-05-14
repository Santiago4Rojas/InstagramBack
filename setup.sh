#!/bin/sh
# Ejecutar una sola vez despues de clonar el repo
set -e
git config core.hooksPath .githooks
chmod +x .githooks/post-merge .githooks/post-checkout
composer install --no-interaction --prefer-dist
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate
fi
php artisan migrate
echo "Setup completado. Los hooks de git quedan activos."
