# Laravel Docker Environment

This project is configured to run fully in Docker for both local development and production.

## Prerequisites
- Docker
- Docker Compose

## Quick Start (Local Development)

1. **Start the containers**
   ```bash
   docker-compose -f docker-compose.local.yml up -d
   ```
   *The first time you run this, it will automatically:*
   - Unpack and install a new Laravel application if one doesn't exist
   - Install Composer dependencies
   - Copy `.env.example` to `.env` and set up database variables
   - Generate your `APP_KEY`
   - Wait for the database to be ready and run migrations

2. **Access the application**
   - Web App: http://localhost:8000
   - The application files are mapped to `./laravel-app` on your host machine.

3. **Stop the containers**
   ```bash
   docker-compose -f docker-compose.local.yml down
   ```

---

## Production Deployment

For production, the configuration includes SSL support via Nginx on port 443.

1. **Add SSL Certificates**
   Place your certificates in a `./ssl` directory in the root of the project:
   - `./ssl/server.crt`
   - `./ssl/server.key`

2. **Start the containers**
   ```bash
   docker-compose -f docker-compose.production.yml up -d
   ```

---

## Working with the Containers

### Entering the Containers

Often you will need to enter the container to run Artisan commands, Composer, or NPM.

**Enter the PHP/Laravel application container:**
```bash
docker exec -it laravel-cityfix-app bash
```

**Enter the Database container:**
```bash
docker exec -it mysql_cityfix bash
# Once inside, you can access mysql:
mysql -u cityfix_user -p
# password: root
```

**Enter the Nginx webserver container:**
```bash
docker exec -it nginx_cityfix sh
```

### Running Laravel Commands (Without entering the container)

You can run commands directly from your host machine by passing them to `docker exec`:

**Run an Artisan command:**
```bash
docker exec -it laravel-cityfix-app php artisan migrate
docker exec -it laravel-cityfix-app php artisan make:controller MyController
```

**Run a Composer command:**
```bash
docker exec -it laravel-cityfix-app composer require <package-name>
docker exec -it laravel-cityfix-app composer dump-autoload
```

### Viewing Logs

To see what is happening in the background:

**View all logs (and follow them live):**
```bash
docker-compose -f docker-compose.local.yml logs -f
```

**View logs for a specific service:**
```bash
docker logs -f laravel-cityfix-app
docker logs -f mysql_cityfix
docker logs -f nginx_cityfix
```

## Structure Overview

- `Dockerfile`: The PHP 8.2 FPM image configuration with all required extensions (GD, Zip, MySQL, etc.)
- `docker-compose.local.yml`: Stack for local development (Port 8000).
- `docker-compose.production.yml`: Stack for production (Ports 80/443 with SSL).
- `nginx.conf`: Local web server configuration.
- `nginx-production.conf`: Production web server configuration with SSL termination.
- `setup-laravel.sh`: Automation script that handles permissions, dependencies, and database migrations.
- `entrypoint.sh`: Executed on container start; runs the setup script before starting PHP-FPM.

## API Documentation

If you are developing the frontend or want to test the API endpoints, please check the [**Payloads.md**](Payloads.md) file.
It contains the completely updated JSON structures, endpoints, multipart form-data requests for image uploads, and route rules for the whole CityFix backend.
