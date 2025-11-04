# 🎬 CineRadar

Sistema completo de catálogo de filmes com geração de conteúdo por IA, otimizado para SEO e responsivo.

## 🚀 Tecnologias

- **Backend**: Laravel 10 + PHP 8.4 + SQLite
- **Frontend**: Vue 3 + Vite + Bulma CSS
- **APIs**: OMDB (filmes) + Google Gemini AI (conteúdo)

---

## 📋 Pré-requisitos

### Backend
- PHP 8.4+ com extensões: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `curl`
- Composer
- Servidor web (Apache/Nginx) ou PHP built-in server

### Frontend
- Node.js 18+ e npm

### Python (para funcionalidades de streaming)
- Python 3.8+
- Bibliotecas: `simplejustwatchapi`
  ```bash
  pip install simplejustwatchapi
  ```

---

## 🛠️ Instalação Local

### 1. Backend (Laravel)

```bash
cd backend

# Instalar dependências
composer install

# Copiar arquivo de ambiente (se necessário)
cp .env.example .env

# Configurar .env com suas chaves de API
# DB_CONNECTION=sqlite
# DB_DATABASE=C:\caminho\completo\para\database\database.sqlite
# OMDB_API_KEY=sua_chave_aqui
# GEMINI_API_KEY=sua_chave_aqui

# Gerar chave da aplicação (se necessário)
php artisan key:generate

# Criar banco de dados SQLite
touch database/database.sqlite  # No Windows: type nul > database\database.sqlite

# Executar migrations
php artisan migrate

# Buscar filmes iniciais (exemplo: 20 filmes)
php artisan fetch:movies --count=20

# Gerar conteúdo AI para os filmes
php artisan generate:movie-ai

# Iniciar servidor de desenvolvimento
php artisan serve
```

O backend estará rodando em: `http://127.0.0.1:8000`

### 2. Frontend (Vue 3)

```bash
cd frontend

# Instalar dependências
npm install

# Iniciar servidor de desenvolvimento
npm run dev
```

O frontend estará rodando em: `http://localhost:5173/cineradar/`

---

## 📦 Deploy em Servidor de Produção

### Backend (Laravel)

#### 1. Preparar arquivos

```bash
cd backend

# Instalar dependências de produção
composer install --optimize-autoloader --no-dev

# Limpar e otimizar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permissões (Linux)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 2. Configurar .env para produção

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com

DB_CONNECTION=sqlite
DB_DATABASE=/caminho/absoluto/para/database/database.sqlite

# Resto das configurações...
```

#### 3. Configurar servidor web

**Apache (.htaccess já incluído no /public)**

```apache
<VirtualHost *:80>
    ServerName seudominio.com
    DocumentRoot /var/www/cineradar/backend/public

    <Directory /var/www/cineradar/backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/cineradar-error.log
    CustomLog ${APACHE_LOG_DIR}/cineradar-access.log combined
</VirtualHost>
```

**Nginx**

```nginx
server {
    listen 80;
    server_name seudominio.com;
    root /var/www/cineradar/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 4. Configurar Cron Jobs

Adicionar ao crontab (`crontab -e`):

```cron
# Executar schedule do Laravel a cada minuto
* * * * * cd /var/www/cineradar/backend && php artisan schedule:run >> /dev/null 2>&1

# OU executar comandos específicos manualmente:
# Buscar novos filmes diariamente às 3h
0 3 * * * cd /var/www/cineradar/backend && php artisan fetch:movies --count=20

# Gerar conteúdo AI diariamente às 4h
0 4 * * * cd /var/www/cineradar/backend && php artisan generate:movie-ai
```

### Frontend (Vue 3)

#### 1. Build de produção

```bash
cd frontend

# Configurar variáveis de ambiente (se necessário)
# Editar vite.config.js para ajustar base path e proxy

# Gerar build otimizado
npm run build
```

Isso gerará a pasta `dist/` com todos os arquivos otimizados.

#### 2. Deploy dos arquivos

**Opção A: Hospedar no mesmo servidor do backend**

```bash
# Copiar arquivos buildados para public do Laravel
cp -r dist/* /var/www/cineradar/backend/public/cineradar/

# OU criar um link simbólico
ln -s /var/www/cineradar/frontend/dist /var/www/cineradar/backend/public/cineradar
```

**Opção B: Hospedar em servidor estático separado (Vercel, Netlify, etc.)**

1. Fazer upload da pasta `dist/` para o serviço
2. Configurar variável de ambiente `VITE_API_URL` para apontar para o backend
3. Atualizar `vite.config.js` antes do build:

```javascript
export default defineConfig({
  plugins: [vue()],
  base: '/',
  define: {
    'process.env.VITE_API_URL': JSON.stringify('https://api.seudominio.com')
  }
})
```

4. Atualizar `src/store/movie.js`:

```javascript
const api = axios.create({
  baseURL: process.env.VITE_API_URL || '/api'
})
```

**Opção C: CDN / Object Storage (S3, Cloudflare R2, etc.)**

```bash
# Fazer upload da pasta dist/ para o bucket
aws s3 sync dist/ s3://seu-bucket/cineradar/ --acl public-read

# Configurar website hosting no bucket
# Configurar CloudFront ou CDN na frente
```

#### 3. Estrutura de arquivos em produção

```
/var/www/cineradar/
├── backend/
│   ├── app/
│   ├── config/
│   ├── database/
│   │   └── database.sqlite
│   ├── public/          ← DocumentRoot do servidor web
│   │   ├── index.php
│   │   └── cineradar/   ← Frontend (opcional)
│   └── ...
└── frontend/
    ├── dist/            ← Arquivos buildados
    └── ...
```

---

## 🔧 Comandos Úteis

### Backend

```bash
# Buscar novos filmes
php artisan fetch:movies --count=10

# Gerar conteúdo AI para todos os filmes sem AI
php artisan generate:movie-ai

# Gerar conteúdo AI para um filme específico
php artisan generate:movie-ai --movie_id=1

# Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver logs
tail -f storage/logs/laravel.log
```

### Frontend

```bash
# Desenvolvimento
npm run dev

# Build de produção
npm run build

# Preview da build
npm run preview
```

---

## 📊 API Endpoints

### Filmes

- `GET /api/movies` - Lista todos os filmes (paginado)
- `GET /api/movies/upcoming` - Filmes futuros
- `GET /api/movies/in-theaters` - Filmes em cartaz
- `GET /api/movies/released` - Filmes lançados
- `GET /api/movie/{slug}` - Detalhes de um filme específico
- `GET /api/movie/{slug}/reviews` - Reviews de um filme

---

## 🔐 Variáveis de Ambiente Importantes

```env
# Aplicação
APP_NAME=CineRadar
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com

# Banco de dados
DB_CONNECTION=sqlite
DB_DATABASE=/caminho/absoluto/database.sqlite

# APIs Externas
OMDB_API_KEY=sua_chave_omdb
GEMINI_API_KEY=sua_chave_gemini

# Cache (para produção, considere Redis)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

---

## 🐛 Troubleshooting

### Backend não inicia
- Verificar se todas as extensões PHP estão instaladas: `php -m`
- Verificar permissões em `storage/` e `bootstrap/cache/`
- Verificar se o arquivo `.env` está configurado corretamente

### Frontend em branco
- Não abrir `index.html` diretamente no navegador
- Sempre usar `npm run dev` para desenvolvimento
- Para produção, fazer `npm run build` e servir a pasta `dist/`

### Erro 500 no backend
- Verificar logs: `storage/logs/laravel.log`
- Verificar se o banco SQLite existe e tem permissões corretas
- Limpar cache: `php artisan cache:clear`

### API não responde
- Verificar se o servidor está rodando
- Verificar configuração de proxy no `vite.config.js`
- Verificar CORS no backend (arquivo `config/cors.php`)

---

## 📝 Licença

Projeto desenvolvido para fins educacionais.

---

## 👥 Autor

CineRadar - Sistema de Catálogo de Filmes com IA
