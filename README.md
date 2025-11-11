# 🎬 CineRadar

Sistema completo de catálogo de filmes com geração de conteúdo por IA, otimizado para SEO e responsivo.

## 🚀 Tecnologias

### Backend

- **Framework**: Laravel 10
- **PHP**: 8.4+
- **Database**: SQLite
- **Cache**: File/Redis
- **APIs Externas**:
  - TMDB (The Movie Database)
  - Google Gemini AI (geração de conteúdo)
  - JustWatch (plataformas de streaming)

### Frontend

- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite
- **UI Framework**: Bulma CSS
- **HTTP Client**: Axios
- **Router**: Vue Router
- **State Management**: Pinia

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

### Backend - Comandos Principais

```bash
# === Buscar Filmes ===
php artisan fetch:movies --count=10

# === Gerar Conteúdo AI ===
php artisan generate:movie-ai                    # Todos os filmes sem AI
php artisan generate:movie-ai --movie_id=1       # Filme específico

# === JustWatch (Plataformas de Streaming) ===
php artisan justwatch:backfill                   # Processar todos (NULL)
php artisan justwatch:backfill --limit=100       # Processar 100 filmes
php artisan justwatch:backfill --year=2024       # Filmes de 2024
php artisan justwatch:backfill --empty           # Reprocessar vazios
php artisan justwatch:backfill --start-id=5000   # Continuar do ID 5000
php artisan justwatch:backfill --sleep=2         # Delay de 2s entre requests

# === Cache ===
php artisan cache:clear                          # Limpar cache
php artisan cache:warmup-explore                 # Aquecer cache de explorar
php artisan config:clear
php artisan route:clear
php artisan view:clear

# === Agendamento ===
php artisan schedule:list                        # Ver comandos agendados
php artisan schedule:run                         # Executar scheduler

# === Logs ===
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

---

## 📚 Documentação Detalhada

### 🎯 Sistema de Ordenação de Filmes

O CineRadar possui um sistema sofisticado de ordenação customizada para as seções principais.

#### Estrutura

**Tabela**: `movie_orderings` (registro único)

| Campo           | Tipo | Descrição                           |
| --------------- | ---- | ------------------------------------- |
| `in_theaters` | JSON | Ordenação para "Em Cartaz"          |
| `upcoming`    | JSON | Ordenação para "Próximas Estreias" |
| `released`    | JSON | Ordenação para "Lançados"          |

#### Funcionamento

O sistema trabalha em **dois níveis**:

1. **Filmes ordenados**: Aparecem primeiro, na ordem definida no JSON
2. **Demais filmes**: Aparecem depois, ordenados por data e popularidade

#### Formato do JSON

```json
[
  {"id_tmdb": 693134, "title": "Duna: Parte Dois"},
  {"id_tmdb": 823464, "title": "Godzilla e Kong: O Novo Império"}
]
```

#### API Endpoints de Ordenação

```bash
# Obter todas as ordenações
GET /api/movie-ordering/all

# Obter ordenação específica
GET /api/movie-ordering/{type}
# type: in_theaters, upcoming ou released

# Atualizar ordenação (para n8n/automação)
POST /api/movie-ordering/{type}
Content-Type: application/json
{
  "ordering": [
    {"id_tmdb": 693134, "title": "Duna: Parte Dois"},
    {"id_tmdb": 823464, "title": "Godzilla e Kong"}
  ]
}
```

#### Integração com n8n

Workflow recomendado:

1. **Trigger**: Manual ou agendado
2. **HTTP Request**: Buscar filmes (TMDB/planilha)
3. **Code Node**: Transformar para formato esperado
4. **HTTP Request**: POST para `/api/movie-ordering/{type}`

#### Lógica de Cada Seção

- **Em Cartaz** (`in_theaters`): Últimos 30 dias
- **Próximas Estreias** (`upcoming`): Filmes futuros
- **Lançados** (`released`): Entre 30 e 7 dias atrás

---

### � JustWatch Backfill

Comando para preencher automaticamente informações de plataformas de streaming.

#### Parâmetros Disponíveis

| Parâmetro      | Descrição                     | Padrão | Exemplo             |
| --------------- | ------------------------------- | ------- | ------------------- |
| `--start-id=` | ID inicial                      | Nenhum  | `--start-id=7292` |
| `--limit=`    | Limite de filmes                | Todos   | `--limit=100`     |
| `--sleep=`    | Delay entre requests (segundos) | 1       | `--sleep=2`       |
| `--year=`     | Filtrar por ano                 | Nenhum  | `--year=2023`     |
| `--empty`     | Apenas JSONs vazios             | false   | `--empty`         |

#### Modos de Operação

**Modo Padrão** (NULL only):

```bash
php artisan justwatch:backfill --limit=50
```

**Modo por Ano**:

```bash
php artisan justwatch:backfill --year=2024 --limit=100
```

**Modo Empty** (Reprocessamento):

```bash
php artisan justwatch:backfill --empty --limit=50
```

**Combinação**:

```bash
php artisan justwatch:backfill --year=2024 --empty --sleep=2
```

#### Casos de Uso

```bash
# Primeira execução - Popular banco
php artisan justwatch:backfill --sleep=1

# Atualizar filmes recentes
php artisan justwatch:backfill --year=2024 --limit=500

# Reprocessar falhas
php artisan justwatch:backfill --empty --sleep=0

# Continuar de onde parou
php artisan justwatch:backfill --start-id=5000

# Teste rápido
php artisan justwatch:backfill --limit=10 --sleep=0
```

#### Boas Práticas

- ✅ Use `--sleep=1` ou mais em produção
- ✅ Processe em lotes com `--limit`
- ✅ Use `--year` para filmes específicos
- ✅ Monitore o log para detectar problemas
- ❌ Evite `--sleep=0` em grandes volumes

---

### 🚀 Sistema de Cache (Páginas de Explorar)

O CineRadar implementa cache inteligente para as páginas de explorar (gêneros, décadas, países).

#### Comando de Aquecimento

```bash
php artisan cache:warmup-explore
```

**Processa**:

- 10 gêneros principais (ação, aventura, comédia, drama, etc)
- 6 décadas (1970s até 2020s)
- 8 países (BR, US, GB, FR, IT, ES, JP, KR)

**Configuração**:

- Cache válido por 2 horas (7200 segundos)
- Limita aos top 200 filmes com 50+ votos
- Ordenado por vote_count e popularity

#### Agendamento Automático

O comando está configurado para rodar automaticamente a cada 2 horas via Laravel Scheduler.

**Arquivo**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('cache:warmup-explore')->everyTwoHours();
}
```

#### Configuração do Cron no Servidor

**1. Editar crontab**:

```bash
crontab -e
```

**2. Adicionar linha**:

```bash
* * * * * cd /var/www/guiadefilmes && php artisan schedule:run >> /dev/null 2>&1
```

**3. Comandos úteis**:

```bash
# Ver logs do cron
grep CRON /var/log/syslog

# Verificar agendamentos ativos
crontab -e

# Ver lista de comandos agendados no Laravel
php artisan schedule:list
```

#### Funcionamento

1. Cron executa `php artisan schedule:run` a cada minuto
2. Laravel verifica quais comandos devem rodar
3. `cache:warmup-explore` executa automaticamente a cada 2 horas
4. Cache das páginas é renovado em background

---

### 🎨 Funcionalidades do Frontend

#### Keep-Alive e Preservação de Estado

O frontend utiliza Vue Router com `keep-alive` para:

- ✅ Manter scroll position ao voltar
- ✅ Preservar filtros e paginação
- ✅ Evitar requisições desnecessárias
- ✅ Melhorar experiência do usuário

**Páginas com keep-alive**:

- Released (Lançamentos)
- Upcoming (Próximas Estreias)
- InTheaters (Em Cartaz)
- GenreMovies (Por Gênero)
- CountryMovies (Por País)
- YearMovies (Por Década)

#### Paginação com URL

Todas as páginas de listagem suportam paginação via URL:

```
/lancamentos?page=2
/proximas-estreias?page=3
/em-cartaz?page=1
/explorar/genero/acao?page=5
/explorar/pais/BR?page=2
/explorar/decada/2020s?page=4
```

**Benefícios**:

- URLs compartilháveis
- Suporte a bookmarks
- Navegação com botões do navegador
- SEO-friendly

#### Sistema de Filtros

**Ratings**:

- Apenas filmes com 50+ votos mostram nota
- Filmes com menos votos exibem "N/A"
- Garante confiabilidade das avaliações

**Ordenação**:

- Ordenação customizada + padrão
- Evita duplicatas na paginação
- Performance otimizada

---

### 🔌 API Endpoints Completos

#### Filmes

```bash
# Listagens
GET /api/movies                    # Todos (paginado)
GET /api/movies/upcoming           # Próximas estreias
GET /api/movies/in-theaters        # Em cartaz
GET /api/movies/released           # Lançados recentemente

# Detalhes
GET /api/movie/{slug}              # Detalhes do filme
GET /api/movie/{slug}/reviews      # Reviews

# Filtros
GET /api/movies/filter             # Busca com filtros
GET /api/movies/decade/{slug}      # Por década (2020s, 2010s, etc)
GET /api/movies/country/{code}     # Por país (BR, US, etc)
GET /api/movies/search?q={query}   # Busca textual
```

#### Ordenação de Filmes

```bash
GET  /api/movie-ordering/all                # Todas as ordenações
GET  /api/movie-ordering/{type}             # Ordenação específica
POST /api/movie-ordering/{type}             # Atualizar ordenação
```

#### JustWatch (Streaming)

```bash
GET /api/justwatch/search?query={title}&release_date={date}
```

---

## 🀽� Licença

Projeto desenvolvido para fins educacionais.

---

## 👥 Autor

CineRadar - Sistema de Catálogo de Filmes com IA

**Última atualização**: Novembro 2025
