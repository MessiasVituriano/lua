#!/bin/bash
set -e

cd /var/www

# Se um comando foi passado ao container, executa e encerra.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Corrigir permissoes do storage (volume montado pode vir como root)
chown -R laravel:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Instalar dependencias PHP se necessario
if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-dev --optimize-autoloader
fi

# node_modules do Windows nao funciona no Linux - reinstalar se necessario
if [ ! -f "node_modules/.linux-built" ]; then
    rm -rf node_modules
    npm install
    touch node_modules/.linux-built
fi

# Buildar assets se nao existirem
if [ ! -f "public/build/manifest.json" ]; then
    npm run build
fi

exec php-fpm
