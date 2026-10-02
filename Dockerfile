# syntax=docker/dockerfile:1

# ---------- Stage 1: build do frontend (Vue) ----------
FROM node:20-alpine AS frontend-build
WORKDIR /frontend
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm install
COPY frontend/ ./
RUN npm run build

# ---------- Stage 2: aplicação Laravel (PHP-FPM, Alpine) ----------
# Alpine + FPM em vez de Debian + Apache: a imagem base Debian+Apache sozinha
# já pesa ~730MB (antes de qualquer dependência nossa) — impossível caber
# num orçamento de imagem pequeno. Alpine+FPM (~130MB de base) + Nginx
# servindo os estáticos (stage 3) é o único jeito realista de ficar magro.
#
# 8.4 (não 8.2): composer.lock exige PHP >=8.4.1 em algumas dependências
# transitivas do Symfony — bate com o que o próprio projeto já documenta
# como requisito (README: "PHP 8.4+").
FROM php:8.4-fpm-alpine AS app

# git/unzip ficam instalados de vez (pequenos no Alpine, poucos MB) porque o
# composer install mais abaixo precisa deles. As ferramentas de compilação
# (autoconf/gcc/g++/make/pkg-config) e os headers -dev só existem durante
# esta camada — "apk del .build-deps" no fim da mesma instrução remove tudo
# de novo antes da camada fechar, então elas NÃO entram na imagem final.
RUN apk add --no-cache \
        git unzip \
        python3 py3-pip ffmpeg \
        libzip oniguruma \
    && apk add --no-cache --virtual .build-deps \
        autoconf gcc g++ make pkgconf \
        libzip-dev oniguruma-dev linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip \
    && apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências PHP (cache de camada separado do restante do código)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Código da aplicação
COPY . .
COPY --from=frontend-build /frontend/dist/. ./public/

RUN composer dump-autoload --optimize \
    && pip3 install --no-cache-dir --break-system-packages -r scripts/requirements.txt

# public/ vira um volume compartilhado com o serviço "webserver" (Nginx) em
# tempo de execução (ver docker-compose*.yml), então o que for copiado aqui
# durante o build seria "tampado" pelo volume ao subir o container. Guarda
# uma cópia à parte ("public-seed") pro entrypoint restaurar dentro do
# volume toda vez que o container inicia — assim o Nginx sempre enxerga o
# build mais recente, sem duplicar a lógica de publicação.
RUN cp -a public/. public-seed/

RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache public/trailers \
    && chown -R www-data:www-data storage bootstrap/cache public/trailers public-seed \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Stage 3: Nginx (só serve os estáticos, encaminha .php pro FPM) ----------
FROM nginx:1.27-alpine AS webserver
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
# public/ aqui também é sobrescrito pelo volume compartilhado em tempo de
# execução — este COPY só evita um 502 nos primeiros segundos antes do
# container "app" terminar de restaurar o volume.
COPY --from=frontend-build /frontend/dist/. /var/www/html/public/
