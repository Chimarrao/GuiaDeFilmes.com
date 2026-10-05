---
name: curiosidades-filmes
description: Gera um novo lote de curiosidades (trivia) verificáveis, escritas pessoalmente (sem IA automatizada/scraping), pra filmes do catálogo GuiaDeFilmes.com que ainda não têm curiosidades, e cria a migration de dados correspondente seguindo o padrão do projeto (coluna `trivia`, barra de progresso). Use quando o usuário pedir pra adicionar/continuar curiosidades de filmes (ex: "/curiosidades-filmes 500", "roda mais um lote de curiosidades").
---

# Curiosidades por filme (trivia)

Gera o próximo lote de curiosidades pra filmes do catálogo que ainda não
foram cobertos. Não tem teto fixo de quantidade — a meta é cobrir o máximo
possível do catálogo, continuando lote após lote até genuinamente esgotar
candidatos em quem você tem confiança real (não até bater um número). Varie
a fonte de candidatos quando uma esgotar (`tmdb_vote_count`, depois nota,
depois filmografia de diretores específicos, franquias, cinema por país,
Oscar por década, etc. — ver seção de estratégias mais abaixo) em vez de
parar só porque uma fonte específica secou.

Tamanho do lote: sem limite fixo — gere quantos lotes numerados (lote_8,
lote_9...) sua confiança permitir numa mesma sessão, cada um como arquivo
separado, validando localmente antes de passar pro próximo.

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

### Teste pra saber se é curiosidade de verdade ou sinopse disfarçada

Um lote inteiro (lote 6) já foi **rejeitado e apagado** por isso: ao não
reconhecer os filmes da lista de candidatos, o gerador escreveu frases que
PARECEM fato mas são só descrição de gênero/tema reformulada — nenhuma é
verificável, nenhuma é específica daquele filme.

❌ Exemplo real rejeitado (filme: "Pai do Ano"):
> "O filme é uma comédia que desafia estereótipos de paternidade moderna."

Isso serve pra qualquer comédia familiar sobre pais — não é uma curiosidade,
é uma sinopse genérica inventada a partir do título/gênero. **Se a frase
ainda faz sentido trocando o nome do filme por outro do mesmo gênero, não é
uma curiosidade válida.**

✅ Exemplo real aceito (filme: Interestelar):
> "O físico Kip Thorne (Nobel de 2017) atuou como consultor científico e
> ajudou a desenhar visualmente o buraco negro Gárgantua."

Isso é específico, nomeável, checável — só é verdade pra esse filme exato.

**Regra prática**: se o filme é obscuro o suficiente pra você não lembrar de
nenhum fato de bastidor/elenco/prêmio/bilheteria específico dele, a resposta
certa é PULAR, nunca é "inventar uma sinopse com cara de fato". Prefira um
lote pequeno e 100% verdadeiro a um lote grande com fatos genéricos.

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

5. **Confirmar que o filme existe no catálogo antes de incluir na migration**:
   um filme pode ter curiosidade escrita mas ainda não estar na base do site
   (catálogo real, via produção). Antes de colocar um `tmdb_id` na migration
   de seed, confirme que ele existe de verdade (`GET
   https://guiadefilmes.com/api/movies/search?q=<título>`, comparando
   `tmdb_id` do resultado). Se não existir:
   - mande ele pro n8n (`POST
     http://163.176.145.249:5678/webhook/buscar-filme?query=<título>`, um a
     um, não em lote — esse workflow importa o filme pro catálogo. Timeout
     real de até 10min por chamada, então isso é lento de propósito);
   - confirme de novo via `/api/movies/search` que ele realmente gravou;
   - só inclua na migration os que confirmaram presença (antes ou depois do
     n8n). Filme que não existe e que o n8n não conseguiu importar fica de
     fora dessa leva — não vira linha morta na migration.

6. **Criar a migration**: copiar o padrão de
   `database/migrations/2026_10_02_010100_seed_trivia_for_popular_movies.php`
   (leitura do JSON, sem guard de ambiente — roda em qualquer ambiente, não
   só produção —, `Movie::where('tmdb_id', ...)->first()?->update(['trivia'
   => $facts])`,
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

Um agente rodando essa skill com modelo **Haiku** (barato) já produziu, em
duas tentativas seguidas, lotes inaceitáveis: uma vez sinopse genérica
disfarçada de fato (~60% do lote), e outra vez **tmdb_ids inventados de
memória** pra filmes famosos (ex: achou que 43075 era Matrix — na
verdade é um filme completamente diferente, sem nenhuma relação).
Isso teria sobrescrito a curiosidade de filmes aleatórios do catálogo
com texto sobre filmes errados, um problema de integridade de dado, não
só de qualidade de texto.

Refeito com **Sonnet** e instrução explícita de nunca escrever um
tmdb_id de memória (só copiar o valor exato retornado por uma chamada
de API real), o resultado saiu correto de primeira — 8/8 ids
conferidos bateram, todos os fatos específicos e verificáveis.

**Conclusão prática**: pra geração de conteúdo (escolher qual fato
escrever) o Haiku costuma ir bem quando o filme já é muito famoso e a
regra de "pular se não tiver certeza" é seguida à risca. Mas pra
qualquer etapa que envolve **recuperar/confirmar um identificador**
(tmdb_id) — onde um erro silencioso corrompe dado de um filme
errado — prefira Sonnet, ou pelo menos audite 100% dos tmdb_ids contra
a API real antes de aceitar um lote gerado por Haiku (nunca aceite o
relatório do agente sem essa conferência independente).
