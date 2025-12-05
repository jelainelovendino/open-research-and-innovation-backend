#!/bin/bash
php artisan migrate --force

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
  php artisan storage:link
fi

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
