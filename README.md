# Starter Kit Laravel Filament

### NOTE
- please change your .env file, change the config database, and app as you want.

## Docker Usage

Change environment and 
```
cp .env.example .env
cp docker-compose.yml.default docker-compose.yml
```

#### Development Environment
```
sh stub/local/setup.sh
```

#### Production Environment
```
sh stub/prod/setup.sh
```

### Docker Usage Note
- On development we use npm package **chokidar** to update change when reload. You can remove `--watch` on **supervisord.conf** or you can choose setup on production mode
- We use Laravel Octane with frankenphp server, you can change or remove it if you don't want use it
- Please consider use default container name logic on docker-compose.yml to run bash script (It use **COINTAINER_NAME** variable on .env file)
- It's free to change database like mysql, mariadb, postgres, etc. But we just use Postgres in this starter kit for example.

### Testing

```
php artisan test --coverage-html storage/app/public/coverage
```

## Just a Note

You can directly show the error on the storage/logs file

test user can be found at database/seeders/UserSeeder.php 

code coverage can be found at <base_url>/storage/coverage/Http/index.html

## Progress
- [x] Docker Config Available
- [x] API Documentation (/documentation)
- [x] Role Permission
- [x] Testing

## Happy Code !
