# JustWatch Backfill Command

Comando Artisan para preencher automaticamente o campo `justwatch_watch_info` na tabela `movies` com informações de plataformas de streaming do JustWatch.

## 📋 Descrição

Este comando processa filmes em lote, consultando o endpoint local `/api/justwatch/search` para cada filme e salvando as informações de onde assistir no banco de dados.

## 🚀 Uso Básico

```bash
php artisan justwatch:backfill [options]
```

## 🔧 Parâmetros Disponíveis

| Parâmetro | Descrição | Padrão | Exemplo |
|-----------|-----------|--------|---------|
| `--start-id=` | ID inicial para começar o processamento | Nenhum | `--start-id=7292` |
| `--limit=` | Limite de filmes a processar | Todos | `--limit=100` |
| `--sleep=` | Delay entre cada request (segundos) | 1 | `--sleep=2` |
| `--year=` | Filtrar filmes de um ano específico | Nenhum | `--year=2023` |
| `--empty` | Processar apenas filmes com JSON vazio | false | `--empty` |

## 📝 Modos de Operação

### 1. Modo Padrão (NULL only)
Processa apenas filmes onde `justwatch_watch_info` é `NULL`:

```bash
php artisan justwatch:backfill --limit=50
```

### 2. Modo por Ano
Filtra filmes de um ano específico, priorizando os que não têm informação:

```bash
php artisan justwatch:backfill --year=2023 --limit=100
```

**Comportamento:**
- Filtra pela coluna `release_date` (ano)
- Ordena colocando os `NULL` primeiro
- Depois processa os que já têm informação

### 3. Modo Empty (Reprocessamento)
Processa apenas filmes que retornaram JSON vazio anteriormente:

```bash
php artisan justwatch:backfill --empty --limit=50
```

**Detecta:**
- `[]` (array vazio)
- `null` (string "null")
- `{}` (objeto vazio)

### 4. Combinação de Modos
Você pode combinar parâmetros:

```bash
# Filmes de 2024 que estão vazios
php artisan justwatch:backfill --year=2024 --empty --limit=50

# Começar do ID 5000, processar 200 filmes, sem delay
php artisan justwatch:backfill --start-id=5000 --limit=200 --sleep=0
```

## 📊 Output do Comando

O comando exibe informações detalhadas durante a execução:

### Cabeçalho
```
== JustWatch Backfill Starting ==
Start ID: 7292
Limit: 100
Sleep: 1s
Year Filter: 2023
Mode: Empty JSON only
Total de filmes a processar: 100
```

### Progresso
Para cada filme processado:
```
[id=7292 tmdb=603] "Matrix" (1999) ✓ OK | 5 offers
[id=7293 tmdb=604] "The Godfather" (1972) ✓ OK | 3 offers
[id=7294 tmdb=605] Sem título, ignorando.
[id=7295 tmdb=606] "Inception" (2010) ERROR: Timeout
```

### Legenda do Output
- **id**: ID do filme no banco de dados
- **tmdb**: ID do filme no The Movie Database
- **"Título"**: Nome do filme
- **(Ano)**: Ano de lançamento (ou "N/A" se não disponível)
- **✓ OK**: Processamento bem-sucedido
- **X offers**: Quantidade de plataformas encontradas

## ⚙️ Funcionamento Interno

### 1. Query de Filmes
```sql
-- Modo Padrão
SELECT id, tmdb_id, title, release_date 
FROM movies 
WHERE justwatch_watch_info IS NULL 
ORDER BY id ASC

-- Modo com Ano
SELECT id, tmdb_id, title, release_date 
FROM movies 
WHERE YEAR(release_date) = 2023 
ORDER BY CASE WHEN justwatch_watch_info IS NULL THEN 0 ELSE 1 END, id ASC

-- Modo Empty
SELECT id, tmdb_id, title, release_date 
FROM movies 
WHERE justwatch_watch_info IN ('[]', 'null', '{}') 
ORDER BY id ASC
```

### 2. Chamada à API
Para cada filme:
```php
GET http://127.0.0.1:8000/api/justwatch/search?query={title}&release_date={release_date}
```

### 3. Salvamento
```php
UPDATE movies 
SET justwatch_watch_info = '{"offers": [...]}'
WHERE tmdb_id = {tmdb_id}
LIMIT 1
```

## 🎯 Casos de Uso

### Primeira Execução (Popular Banco)
```bash
# Começar do início, processar tudo
php artisan justwatch:backfill --sleep=1
```

### Atualizar Filmes Recentes
```bash
# Filmes de 2024 e 2025
php artisan justwatch:backfill --year=2024 --limit=500
php artisan justwatch:backfill --year=2025 --limit=500
```

### Reprocessar Falhas
```bash
# Apenas os que retornaram vazio
php artisan justwatch:backfill --empty --sleep=0
```

### Continuar de Onde Parou
```bash
# Se parou no ID 5000
php artisan justwatch:backfill --start-id=5000
```

### Teste Rápido
```bash
# Processar apenas 10 filmes para testar
php artisan justwatch:backfill --limit=10 --sleep=0
```

## ⚠️ Considerações Importantes

### Performance
- **Sleep recomendado**: 1-2 segundos para evitar sobrecarga da API do JustWatch
- **Timeout**: 20 segundos por request
- **Processamento em lote**: Use `--limit` para processar em partes

### Erros Comuns
1. **Timeout**: API do JustWatch demorou mais de 20s
2. **Sem título**: Filme sem título no banco
3. **HTTP Error**: Problema na conexão com o endpoint local

### Boas Práticas
- ✅ Use `--sleep=1` ou mais em produção
- ✅ Processe em lotes com `--limit`
- ✅ Use `--year` para atualizar filmes específicos
- ✅ Monitore o log para detectar problemas
- ❌ Evite `--sleep=0` em grandes volumes
- ❌ Não processe tudo de uma vez sem limite

## 🔍 Troubleshooting

### "Nenhum filme para processar"
- Verifique se existem filmes com `justwatch_watch_info = NULL`
- Se usando `--year`, confirme se há filmes daquele ano
- Se usando `--empty`, confirme se há filmes com JSON vazio

### Erro HTTP 500
- Verifique se o servidor Laravel está rodando
- Confirme que o endpoint `/api/justwatch/search` está acessível
- Teste manualmente: `curl http://127.0.0.1:8000/api/justwatch/search?query=Avatar`

### Timeout Frequente
- Aumente o valor de `--sleep`
- Reduza o `--limit` para processar menos filmes por vez
- Verifique a conexão de internet

### Caracteres Especiais
- O sistema já trata UTF-8 automaticamente
- Se houver problemas, verifique o encoding do banco de dados

## 📈 Monitoramento

### Durante a Execução
Acompanhe:
- ✅ Taxa de sucesso (OK vs ERROR)
- ✅ Quantidade de offers por filme
- ✅ Filmes sem título
- ✅ Progresso da barra

### Após a Execução
Verifique no banco:
```sql
-- Filmes processados
SELECT COUNT(*) FROM movies WHERE justwatch_watch_info IS NOT NULL;

-- Filmes vazios
SELECT COUNT(*) FROM movies WHERE justwatch_watch_info = '[]';

-- Filmes ainda NULL
SELECT COUNT(*) FROM movies WHERE justwatch_watch_info IS NULL;
```

## 🔄 Agendamento (Opcional)

Para atualizar automaticamente no Laravel Scheduler:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Processar 100 filmes NULL todo dia às 3h
    $schedule->command('justwatch:backfill --limit=100 --sleep=2')
             ->dailyAt('03:00');
    
    // Atualizar filmes do ano atual toda semana
    $schedule->command('justwatch:backfill --year=' . date('Y') . ' --limit=500')
             ->weekly();
}
```

## 📚 Referências

- **Endpoint API**: `GET /api/justwatch/search`
- **Script Python**: `backend/scripts/justwatch.py`
- **Controller**: `app/Http/Controllers/JustWatchController.php`
- **Tabela**: `movies.justwatch_watch_info` (JSON)

---

**Última atualização**: Novembro 2025  
**Versão**: 2.0 (com suporte a --year e --empty)
