##############################################
# Stage 1: Build frontend assets
##############################################
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/
RUN npm run build

##############################################
# Stage 2: PHP + Nginx production image
##############################################
FROM php:8.2-fpm

# Instalar dependencias do sistema + Nginx + Supervisor
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar arquivos do projeto
COPY . .

# Copiar assets buildados do stage 1
COPY --from=frontend /app/public/build ./public/build

# Instalar dependencias PHP (producao)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissoes do storage e cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configuracao Nginx (template com $PORT dinamico)
RUN rm /etc/nginx/sites-enabled/default
COPY docker/nginx/render.conf.template /etc/nginx/templates/default.conf.template

# Configuracao Supervisor (Nginx + PHP-FPM)
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar entrypoint de producao
COPY docker/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=10000
EXPOSE 10000

CMD ["entrypoint.sh"]
