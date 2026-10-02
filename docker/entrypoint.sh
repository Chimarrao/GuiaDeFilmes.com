#!/bin/sh
set -e

cd /var/www/html

# Reaplica a permissão: um volume nomeado montado por cima de storage/ e
# bootstrap/cache/ pode trazer dono diferente do que o Dockerfile define no
# build, então garante de novo aqui toda vez que o container sobe.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# public/ é um volume compartilhado com o serviço "webserver" (Nginx) — o
# volume tampa o que foi copiado ali durante o build, então restaura aqui
# a cada start. cp sem --delete: preserva arquivos gerados em runtime que
# não fazem parte do build (public/trailers/*, public/sitemap*.xml).
cp -rf public-seed/. public/ 2>/dev/null || true
chown -R www-data:www-data public 2>/dev/null || true

if [ ! -f .env ]; then
  echo "==> .env não encontrado, copiando de .env.example..."
  cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
  echo "==> Gerando APP_KEY..."
  php artisan key:generate --force
fi

if [ -n "$DB_HOST" ]; then
  echo "==> Aguardando o banco de dados em ${DB_HOST}:${DB_PORT:-3306}..."
  for i in $(seq 1 30); do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; then
      echo "==> Banco disponível."
      break
    fi
    sleep 2
  done
fi

if [ "$SKIP_MIGRATE" = "1" ]; then
  echo "==> SKIP_MIGRATE=1, não roda migrate aqui (outro serviço cuida disso)."
else
  echo "==> Rodando migrations..."
  php artisan migrate --force || echo "==> AVISO: migrate falhou, siga mesmo assim (rode manualmente depois)."
fi

exec "$@"
