# --- Stage 1: Build the React frontend ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app/frontend

# Install dependencies
COPY frontend/package*.json ./
RUN npm install

# Build the app
COPY frontend/ ./
RUN npm run build

# --- Stage 2: Build the Laravel backend ---
FROM composer:2 AS backend-builder
WORKDIR /app/backend

# Install dependencies
COPY backend/composer*.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy app code
COPY backend/ ./
RUN composer dump-autoload --optimize

# --- Stage 3: Final Production Image ---
FROM dunglas/frankenphp:1.3-php8.3-alpine

# Install PHP extensions required for Laravel
RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    bcmath \
    redis

WORKDIR /app

# Copy the backend from stage 2
COPY --from=backend-builder /app/backend /app

# Copy the frontend build into Laravel's public folder
# This makes it accessible to the web server
COPY --from=frontend-builder /app/frontend/build /app/public

# Copy custom Caddyfile for SPA + API routing
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Move any existing .env safely if needed, or rely on Railway Env Vars
# RUN cp .env.example .env

# Set environment variables for FrankenPHP
ENV PORT=8080
ENV SERVER_NAME=:${PORT}
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8080

# Command to run migrations and start the server
# We use ';' to ensure the server starts even if migrations fail (which prevents 502 Boot Loops)
CMD ["sh", "-c", "php artisan migrate --force; frankenphp run --config /etc/caddy/Caddyfile"]

