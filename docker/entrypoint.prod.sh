#!/bin/bash
set -e

cd /var/www

# Porta padrão do Render é 10000
export PORT="${PORT:-10000}"

# Gerar Nginx config a partir do template
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default

# Gerar APP_KEY se nao existir ou estiver vazia
if [ -z "$APP_KEY" ] || [[ "$APP_KEY" != base64:* ]]; then
    php artisan key:generate --force
fi

# Cache de configuracao e rotas para producao
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Rodar migrations automaticamente
php artisan migrate --force

# Garantir link do storage
php artisan storage:link 2>/dev/null || true

echo "==> App rodando na porta $PORT"

# Iniciar Supervisor (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
