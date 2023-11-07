#!/bin/bash

cd /var/www/html

# Update composer
sudo composer install --no-dev --no-scripts --optimize-autoloader

# Update ?v= timestamps for css and js files because they are cached by CDN

TIME=$(date +%s)
BLADE_PATH="resources/views/layouts/default.blade.php"

sudo sed -i "s/css?v=.*\"/css?v=$TIME\"/g" $BLADE_PATH
sudo sed -i "s/js?v=.*\"/js?v=$TIME\"/g" $BLADE_PATH

# Setup the various file and folder permissions for Laravel
sudo chown -R ubuntu:www /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo chgrp -R www storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

# Compress main.js to main.min.js
curl -X POST -s --data-urlencode 'input@public/js/main.js' https://javascript-minifier.com/raw > public/js/main.min.js

# Optimize
php artisan cache:clear
php artisan view:clear
php artisan optimize
php artisan config:cache
php artisan route:cache

# Restart laravel queue workers by restarting supervisor
sudo supervisorctl restart all