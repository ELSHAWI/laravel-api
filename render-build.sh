#!/bin/bash
# Clear ALL caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Verify active connection
echo "ACTIVE DB: $(php artisan tinker --execute="echo \DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);")"

# Optional: Log full database config
php artisan tinker --execute="dd(config('database'))" > storage/logs/db_config.log