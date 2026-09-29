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

# 1. Install extension sistem dan PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Set Working Directory
WORKDIR /var/www/html

# 4. COPY SELURUH SOURCE CODE TERLEBIH DAHULU (agar file artisan & folder app tersedia)
COPY . .

# 5. Copy hasil build frontend dari stage 1
COPY --from=frontend /app/public/build ./public/build

# 6. Jalankan composer install (sekarang artisan sudah ada, sehingga package:discover tidak akan error)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 7. Expose Port & Command untuk menjalankan aplikasi
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]