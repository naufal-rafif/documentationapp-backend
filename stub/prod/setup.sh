CONTAINER_NAME=$(grep ^CONTAINER_NAME= .env | cut -d '=' -f2 | tr -d '"')

CONTAINER_NAME=${CONTAINER_NAME:-starter-project}
docker compose up -d

docker exec $CONTAINER_NAME composer install

docker exec $CONTAINER_NAME chmod -R ugo+rw vendor/
docker exec $CONTAINER_NAME chmod -R ugo+rw bootstrap/cache/
docker exec $CONTAINER_NAME chmod -R ugo+rw storage/

docker exec $CONTAINER_NAME chmod ugo+rw composer.lock
docker exec $CONTAINER_NAME chmod ugo+rw composer.json

docker exec $CONTAINER_NAME php artisan migrate --seed
docker exec $CONTAINER_NAME cp stub/local/frankenphp frankenphp
docker exec $CONTAINER_NAME php artisan octane:install --server=frankenphp