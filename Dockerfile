# --- Stage 1: Build Frontend (Vite) ---
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources resources
COPY public public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

# --- Stage 2: PHP Application ---
FROM php:8.3-cli AS stage-1

# Install PostgreSQL & System Dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source code & assets
COPY . .
COPY --from=frontend /app/public/build ./public/build

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Pastikan permission folder storage & cache aman
RUN chmod -R 777 storage bootstrap/cache

# Jalankan server
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]