# Load environment variables from .env
DB_HOST=$(grep '^DB_HOST=' .env | cut -d '=' -f2 | tr -d '"')
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d '=' -f2 | tr -d '"')

# Get container name from DB_HOST by stripping -pgsql
CONTAINER_NAME=${DB_HOST%-pgsql}

# Fallback if something fails
CONTAINER_NAME=${CONTAINER_NAME:-starter-project}
DB_USERNAME=${DB_USERNAME:-postgres}

echo "Container Name: $CONTAINER_NAME"
echo "PostgreSQL Username: $DB_USERNAME"

# Start containers
docker compose up -d 

# Wait for PostgreSQL to be ready
until docker exec "$CONTAINER_NAME" pg_isready -h "${CONTAINER_NAME}-pgsql" -p 5432 -U "$DB_USERNAME"; do
  echo "Waiting for PostgreSQL to be ready..."
  sleep 2
done

# Laravel setup
docker exec "$CONTAINER_NAME" composer install
docker exec "$CONTAINER_NAME" chmod -R ugo+rw vendor/ bootstrap/cache/ storage/
docker exec "$CONTAINER_NAME" chmod ugo+rw composer.lock composer.json
docker exec "$CONTAINER_NAME" php artisan migrate --seed

# Extra setup
docker exec "$CONTAINER_NAME" npm install chokidar
docker exec "$CONTAINER_NAME" cp stub/local/frankenphp frankenphp
docker exec "$CONTAINER_NAME" php artisan octane:install --server=frankenphp
docker exec "$CONTAINER_NAME" mkdir -p config/caddy/
docker exec "$CONTAINER_NAME" chmod -R ugo+rw config/caddy/
docker exec "$CONTAINER_NAME" php artisan storage:link
