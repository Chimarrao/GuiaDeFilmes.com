---
name: curiosidades-filmes
description: Gera um novo lote de curiosidades (trivia) verificáveis, escritas pessoalmente (sem IA automatizada/scraping), pra filmes do catálogo GuiaDeFilmes.com que ainda não têm curiosidades, e cria a migration de dados correspondente seguindo o padrão do projeto (coluna `trivia`, barra de progresso). Use quando o usuário pedir pra adicionar/continuar curiosidades de filmes (ex: "/curiosidades-filmes 500", "roda mais um lote de curiosidades").
---

# Curiosidades por filme (trivia)

Gera o próximo lote de curiosidades pra filmes do catálogo que ainda não
foram cobertos, na ordem de `tmdb_vote_count` (proxy de "mais conhecidos",
já que o projeto não rastreia pageview por filme).

Tamanho do lote: pega de `args` se informado (ex: "500"), senão usa 1000.

## Regra inegociável

Cada curiosidade é **escrita pessoalmente por você (Claude)**, com base em
conhecimento público verificável (bilheteria, prêmios, bastidores, elenco,
curiosidades de produção) — **nunca**:
- gerada por uma API de IA externa sem curadoria própria;
- copiada/raspada de IMDb, Wikipedia ou qualquer fonte (scraping viola ToS);
- inventada sem ter certeza razoável de que é verdade.

Se não tiver confiança sobre um filme específico (pouco conhecido, poucos
dados na memória), **pule esse filme** e segue pro próximo da lista — não
force 2 fatos fracos/genéricos só pra bater a meta.

## Passo a passo

1. **Descobrir quem já foi coberto**: listar todas as chaves (tmdb_id) de
   TODOS os arquivos `database/data/movie_trivia*.json` já existentes no
   repo (cada um é um `{tmdb_id: [fato1, fato2]}`). Essa união é o conjunto
   "já feito" — nunca reescrever um filme que já está em algum desses
   arquivos.

2. **Buscar a lista de candidatos**: usar a API pública de produção
   (`https://guiadefilmes.com/api/movies?sort=popularity&per_page=N`, ou
   paginar com `page=`) ordenada por `tmdb_vote_count` desc — **não** usar
   `popularity` puro (isso traz título obscuro/trending, não os mais
   conhecidos). Filtrar fora os tmdb_ids já cobertos (passo 1) e pegar os
   próximos N da fila (N = tamanho do lote pedido).

3. **Escrever as curiosidades**: pra cada filme do lote, 2 fatos curtos em
   português, cada um uma frase só, factual e específico do filme (não
   genérico tipo "foi um sucesso de bilheteria" sem número/contexto). Usar
   o script incremental em `/tmp/gen_trivia_batch.py` com um dict Python
   `TRIVIA = {tmdb_id: [fato1, fato2], ...}`, construído aos poucos via
   `Edit` (sempre substituindo o `}` final por novas entradas + `}` de
   novo), verificando com `exec()` + `len(ns['TRIVIA'])` a cada leva.
   Motivo de usar Python: é mais rápido de editar incrementalmente que JSON
   puro, e no fim um bloco `__main__` faz `json.dump()` pro arquivo final.

4. **Nome do arquivo de dados**: `database/data/movie_trivia_lote_<N>.json`,
   onde `<N>` é o próximo número livre (olhar quantos `movie_trivia*.json`
   já existem — o primeiro lote histórico é só `movie_trivia.json`, sem
   número, os seguintes são `_lote_2`, `_lote_3`, etc.).

5. **Criar a migration**: copiar o padrão de
   `database/migrations/2026_10_02_010100_seed_trivia_for_popular_movies.php`
   (guard de produção `app()->environment('production')`, leitura do JSON,
   `Movie::where('tmdb_id', ...)->first()?->update(['trivia' => $facts])`,
   **sempre com a barra de progresso** via
   `Symfony\Component\Console\Helper\ProgressBar` — é o padrão fixado
   depois que uma migration sem barra pareceu travada e foi interrompida
   no meio em produção, deixando uma coluna órfã). Nome do arquivo:
   `database/migrations/<timestamp>_seed_trivia_lote_<N>.php` com timestamp
   novo (maior que o último migration existente).

6. **Validar localmente antes de considerar pronto**: sandbox SQLite
   isolado (`cp .env .env.bak`, trocar `DB_CONNECTION=sqlite` +
   `DB_DATABASE` absoluto, `touch database/database.sqlite`,
   `php artisan migrate --force`), criar 2-3 filmes de teste com tmdb_ids
   que estão no lote novo, rodar a migration nova com
   `APP_ENV=production php artisan migrate --force` (só ela, via
   `--path=database/migrations/<arquivo>.php` se já tiver rodado as
   outras, ou do zero) e confirmar que a barra aparece e os dados batem.
   Limpar tudo depois (`rm database/database.sqlite`, restaurar `.env`,
   `git checkout -- storage/framework/cache/data/` se sujar).

7. **Nunca commitar sem autorização explícita** — essa é uma regra
   permanente do projeto, vale igual pra esse lote de curiosidades.

## Lição aprendida (não repetir)

Uma migration de seed sem saída no terminal, processando centenas de
registros, parece travada mesmo rodando rápido — isso já causou uma
interrupção manual em produção que deixou uma `ALTER TABLE` aplicada sem
o registro correspondente na tabela `migrations` (porque DDL no MySQL
não é transacional, comita na hora, mesmo que o restante do script seja
interrompido depois). Por isso a barra de progresso não é cosmética, é
pra evitar esse exato problema de novo.
