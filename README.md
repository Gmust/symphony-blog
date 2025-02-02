# Symfony Docker

A Docker-based installer and runtime for the Symfony web framework, with FrankenPHP and Caddy inside!

## CI

## Getting Started

1. If not already done, install Docker Compose (v2.10+)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait` to set up and start a fresh Symfony project
4. Open [https://localhost](https://localhost) in your favorite web browser and accept the auto-generated TLS certificate
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Additional Commands

- Run `docker compose build --no-cache` to build the Docker images.
- Run `npm install && npm run build` to build the Tailwind CSS assets.
- Run `php bin/console taiwlind:build` to build the Tailwind CSS assets.
- Check the [API documentation on Postman](https://www.postman.com/science-saganist-83669507/workspace/php-blog/collection/17402490-59e58297-d950-48ab-983c-8f0252f55f2d?action=share&creator=17402490) for more details.

Enjoy!


