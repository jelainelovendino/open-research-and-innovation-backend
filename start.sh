#!/bin/bash
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
