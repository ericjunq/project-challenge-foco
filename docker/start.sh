set -e

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan xml:import

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload