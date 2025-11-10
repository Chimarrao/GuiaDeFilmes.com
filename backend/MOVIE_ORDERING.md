# Sistema de Ordenação de Filmes

Sistema para gerenciar a ordem de exibição dos filmes nas seções de "Em Cartaz", "Próximas Estreias" e "Lançados".

## 📋 Estrutura da Tabela

**Tabela**: `movie_orderings`

| Campo           | Tipo      | Descrição                           |
| --------------- | --------- | ------------------------------------- |
| `id`          | bigint    | ID único                             |
| `in_theaters` | JSON      | Ordenação para "Em Cartaz"          |
| `upcoming`    | JSON      | Ordenação para "Próximas Estreias" |
| `released`    | JSON      | Ordenação para "Lançados"          |
| `created_at`  | timestamp | Data de criação                     |
| `updated_at`  | timestamp | Data de atualização                 |

> **Nota**: A tabela tem apenas 1 registro que é criado automaticamente na migration.

## 🎯 Funcionamento

### 1. Ordenação Customizada + Padrão

O sistema funciona em **dois níveis**:

1. **Filmes ordenados**: Aparecem primeiro, na ordem definida no JSON
2. **Demais filmes**: Aparecem depois, ordenados por data e popularidade

### 2. Formato do JSON

Cada campo JSON contém um array de objetos com:

```json
[
  {
    "id_tmdb": 693134,
    "title": "Duna: Parte Dois"
  },
  {
    "id_tmdb": 823464,
    "title": "Godzilla e Kong: O Novo Império"
  }
]
```

**Campos obrigatórios**:

- `id_tmdb` (integer): ID do filme no The Movie Database
- `title` (string): Título do filme (para referência)

## 🔌 API Endpoints

### 1. Obter todas as ordenações

```http
GET /api/movie-ordering/all
```

**Response**:

```json
{
  "in_theaters": [...],
  "upcoming": [...],
  "released": [...]
}
```

### 2. Obter ordenação específica

```http
GET /api/movie-ordering/{type}
```

**Parâmetros**:

- `type`: `in_theaters`, `upcoming` ou `released`

**Response**:

```json
{
  "type": "in_theaters",
  "ordering": [
    {
      "id_tmdb": 693134,
      "title": "Duna: Parte Dois"
    }
  ]
}
```

### 3. Atualizar ordenação (n8n)

```http
POST /api/movie-ordering/{type}
Content-Type: application/json

{
  "ordering": [
    {"id_tmdb": 693134, "title": "Duna: Parte Dois"},
    {"id_tmdb": 823464, "title": "Godzilla e Kong"}
  ]
}
```

**Response**:

```json
{
  "success": true,
  "type": "in_theaters",
  "count": 2,
  "message": "Ordering updated successfully"
}
```

## 🤖 Integração com n8n

### Workflow Recomendado

1. **Trigger**: Execução manual ou agendada
2. **HTTP Request**: Buscar filmes de uma fonte (TMDB, planilha, etc)
3. **Code Node**: Transformar para o formato esperado:
   ```javascript
   const movies = $input.all().map(item => ({
     id_tmdb: item.json.tmdb_id,
     title: item.json.title
   }));

   return [{
     json: {
       ordering: movies
     }
   }];
   ```
4. **HTTP Request**: POST para o endpoint
   - URL: `https://seu-dominio.com/api/movie-ordering/in_theaters`
   - Method: POST
   - Body: `{{ $json.ordering }}`

### Exemplo de Payload Completo

```json
{
  "ordering": [
    {"id_tmdb":693134,"title":"Duna: Parte Dois"},
    {"id_tmdb":823464,"title":"Godzilla e Kong: O Novo Império"},
    {"id_tmdb":932420,"title":"Rivais"},
    {"id_tmdb":653346,"title":"Planeta dos Macacos: O Reinado"},
    {"id_tmdb":786891,"title":"Furiosa: Uma Saga Mad Max"},
    {"id_tmdb":533535,"title":"Deadpool & Wolverine"},
    {"id_tmdb":1022789,"title":"Divertida Mente 2"},
    {"id_tmdb":748783,"title":"Garfield: Fora de Casa"}
  ]
}
```

## 📊 Como Funciona a Ordenação

### Exemplo: Em Cartaz (in_theaters)

**Cenário**:

- Range de datas: Últimos 30 dias
- Ordenação customizada tem 5 filmes
- Database tem 50 filmes nesse período

**Resultado**:

1. **Posições 1-5**: Os 5 filmes da ordenação customizada (ordem exata do JSON)
2. **Posições 6-50**: Os outros 45 filmes (ordenados por release_date DESC, popularity DESC)

### Página 1 (20 filmes)

```
[1-5]  = Filmes customizados
[6-20] = Primeiros 15 filmes da ordenação padrão
```

### Página 2 (20 filmes)

```
[21-40] = Continuação da ordenação padrão
```

### Página 3 (10 filmes)

```
[41-50] = Restante dos filmes
```

## 🎨 Lógica de Cada Seção

### 1. Em Cartaz (in_theaters)

- **Filtro**: `release_date BETWEEN (hoje - 30 dias) AND hoje`
- **Ordenação padrão**: `release_date DESC, popularity DESC`
- **Uso**: Filmes que acabaram de estrear ou estão em cartaz

### 2. Próximas Estreias (upcoming)

- **Filtro**: `release_date > hoje`
- **Ordenação padrão**: `release_date ASC, popularity DESC`
- **Uso**: Filmes que ainda vão estrear

### 3. Lançados (released)

- **Filtro**: `release_date BETWEEN (hoje - 30 dias) AND (hoje - 7 dias)`
- **Ordenação padrão**: `release_date DESC, popularity DESC`
- **Uso**: Filmes que estrearam há pouco tempo mas já saíram de cartaz

## 🔧 Manutenção

### Verificar ordenação atual

```sql
SELECT * FROM movie_orderings;
```

### Limpar ordenação de uma seção

```sql
UPDATE movie_orderings 
SET in_theaters = '[]' 
WHERE id = 1;
```

### Verificar quantos filmes estão na ordenação

```sql
SELECT 
  JSON_LENGTH(in_theaters) as in_theaters_count,
  JSON_LENGTH(upcoming) as upcoming_count,
  JSON_LENGTH(released) as released_count
FROM movie_orderings;
```

### Buscar filme específico na ordenação

```sql
SELECT 
  JSON_SEARCH(in_theaters, 'one', '693134', NULL, '$[*].id_tmdb') as position
FROM movie_orderings;
```

## ⚠️ Considerações Importantes

### Performance

- ✅ A query separa os filmes em dois grupos eficientemente
- ✅ Paginação funciona normalmente
- ⚠️ Com muitos filmes na ordenação customizada (>100), considere cache
- ⚠️ O JSON não deve ser muito grande (máximo 500 filmes recomendado)

### Validação

- ✅ API valida que `id_tmdb` é integer
- ✅ API valida que `title` é string
- ✅ API valida que `type` é válido (`in_theaters`, `upcoming`, `released`)
- ❌ API **NÃO** valida se o `id_tmdb` existe no banco (por design)

### Comportamento

- Se ordenação está vazia `[]`, usa apenas ordenação padrão
- Se filme da ordenação não está no range de datas, ele é ignorado
- Se filme da ordenação não existe no banco, ele é ignorado
- A paginação funciona normalmente mesmo com ordenação customizada

## 🎯 Casos de Uso

### 1. Destacar lançamentos importantes

```json
POST /api/movie-ordering/in_theaters
{
  "ordering": [
    {"id_tmdb": 693134, "title": "Duna: Parte Dois"},
    {"id_tmdb": 533535, "title": "Deadpool & Wolverine"}
  ]
}
```

### 2. Promover franquias

```json
POST /api/movie-ordering/released
{
  "ordering": [
    {"id_tmdb": 120, "title": "O Senhor dos Anéis: A Sociedade do Anel"},
    {"id_tmdb": 121, "title": "O Senhor dos Anéis: As Duas Torres"},
    {"id_tmdb": 122, "title": "O Senhor dos Anéis: O Retorno do Rei"}
  ]
}
```

### 3. Destacar sucessos de bilheteria

```json
POST /api/movie-ordering/upcoming
{
  "ordering": [
    {"id_tmdb": 823464, "title": "Godzilla e Kong: O Novo Império"},
    {"id_tmdb": 1022789, "title": "Divertida Mente 2"}
  ]
}
```

## 🔍 Troubleshooting

### Ordenação não está aparecendo

1. Verificar se o filme existe no banco: `SELECT * FROM movies WHERE tmdb_id = 693134`
2. Verificar se está no range de datas da seção
3. Verificar JSON no banco: `SELECT in_theaters FROM movie_orderings`

### Filme aparece duas vezes

- Isso **NÃO** deve acontecer
- Se acontecer, verificar se o `tmdb_id` está duplicado no JSON

### Paginação estranha

- Verificar se o `limit` está correto no request
- Verificar se a página existe: `page <= ceil(total / limit)`

### Erro ao salvar ordenação

- Verificar formato do JSON
- Verificar se `id_tmdb` é número
- Verificar se `title` é string

## 📚 Referências

- **Controller**: `app/Http/Controllers/MovieOrderingController.php`
- **Model**: `app/Models/MovieOrdering.php`
- **Migration**: `database/migrations/2025_11_09_023255_create_movie_orderings_table.php`
- **Routes**: `routes/api.php`
- **Lógica de ordenação**: `app/Http/Controllers/MovieController.php` (métodos `upcoming`, `inTheaters`, `released`)

---

**Última atualização**: Novembro 2025
**Versão**: 1.0
