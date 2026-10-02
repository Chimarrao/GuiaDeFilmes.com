# Estudo: fontes alternativas de capa, sinopse e fotos de elenco

Contexto que motivou o estudo: filmes de nicho ou muito distantes no calendário
(ex.: **The Chosen: Crucifixion**, `tmdb_id 1487395`, lançamento previsto pra
março/2027) aparecem no site sem pôster e sem sinopse. Confirmei isso puxando
o registro real do nosso banco:

```
tmdb_id: 1487395
poster_url: None
imdb_poster_url: None
synopsis: ''
imdb_synopsis: None
external_ids: None
cast: [15 atores, TODOS com profile_path preenchido]
```

Ou seja: o **TMDB já tem o elenco completo com fotos**, mas **ainda não tem
pôster nem sinopse** cadastrados pra esse título — e como não existe
`external_ids.imdb_id`, o IMDb provavelmente também ainda não criou a própria
página do filme (comum pra lançamentos com mais de 1 ano de antecedência).
Isso não é um bug do nosso código: é a fonte de dados (TMDB) que ainda não
tem o dado, porque ninguém contribuiu com essa informação lá ainda.

---

## 1) De onde tirar pôster/capa quando o TMDB não tem

| Fonte | O que oferece | Custo | Observação legal/técnica |
|---|---|---|---|
| **TMDB (re-checagem periódica)** | O próprio TMDB é alimentado pela comunidade — pôsteres tendem a aparecer lá conforme a data de lançamento se aproxima (geralmente 1-3 meses antes) | Grátis (já usamos) | **Recomendado como primeira linha**: já temos `movies:refresh-existing`, que já traz dados atualizados do TMDB pra filmes existentes — só falta ele também trazer `poster_path`/`overview` quando ficarem disponíveis (hoje o comando já atualiza isso, então helper natural: rodar com mais frequência pra filmes "upcoming" sem pôster) |
| **Fanart.tv** (`fanart.tv`, tem API) | Banco de arte alternativa (pôsteres, banners, logos) por `tmdb_id`/`imdb_id`, mantido por comunidade de fãs, geralmente tem mais variedade que o TMDB pra franquias populares | Grátis (precisa de API key, `personal` tier grátis) | Legítimo, feito pra ser consumido via API. Cobertura menor que TMDB pra filmes muito novos/obscuros, mas vale como segunda tentativa |
| **TheTVDB** (`thetvdb.com`) | "The Chosen" nasceu como série (TV), e a TheTVDB costuma ter arte de séries/temporadas mais cedo que o TMDB trata os recortes-filme da mesma obra | Grátis com cadastro (v4 API tem free tier) | Só relevante pra casos específicos como este (produções que são recortes de séries pra cinema) |
| **Site oficial do estúdio / press kit** | Angel Studios (dona de "The Chosen") publica material oficial de imprensa (banners, pôster oficial) no blog/press page deles antes da estreia | Grátis, mas manual | Sem API — precisaria de curadoria manual pontual pra títulos importantes que a comunidade ainda não populou, não dá pra automatizar sem risco de pegar imagem errada |
| **IMPAwards / Google Imagens / scraping genérico** | Existem sites que arquivam pôster oficial antes de outras fontes | — | **Não recomendo perseguir isso**: IMPAwards não tem API (só site pra navegação humana), e Google/Bing Image Search teria problema de direitos autorais pra automatizar sem controle — risco de baixar imagem errada ou de uso indevido |

**Recomendação prática**: nenhuma mudança de infraestrutura é necessária — o
job/command que já existe (`RefreshExistingMovies` → hoje agendado
`--recent=1000 --old=1000` uma vez por dia) já vai capturar o pôster
automaticamente assim que o TMDB o tiver. Dá pra acelerar isso especificamente
pra filmes "upcoming" sem pôster com uma passada mais frequente (ex.: a cada
6h, só pra quem está com `poster_url IS NULL AND status = 'upcoming'`), sem
precisar de nenhuma fonte nova. Fanart.tv seria o único acréscimo de fonte que
vale a pena, como fallback pra quando o TMDB genuinamente nunca tiver a
imagem (franquias grandes o suficiente pra ter fanart mas pequenas o
suficiente pra nunca ganhar pôster oficial no TMDB — caso raro).

---

## 2) De onde tirar sinopse e fotos de elenco quando o TMDB não tem

Fotos de elenco **já não são o problema** nesse exemplo específico (o TMDB
trouxe as 15 fotos certinho). O gargalo real é **sinopse** pra títulos sem
`overview` no TMDB e sem `imdb_id` ainda.

| Fonte | O que oferece | Custo | Observação |
|---|---|---|---|
| **Wikipedia (REST API `/page/summary/{título}`)** | Resumo curto (1-2 parágrafos) do artigo da Wikipedia, quando existe. Franquias populares como "The Chosen" costumam ter artigo (ou seção dentro do artigo da série) bem antes do lançamento | Grátis, sem key, licença CC-BY-SA (precisa creditar) | **Melhor fallback disponível hoje**: dá pra tentar isso automaticamente quando `synopsis` e `imdb_synopsis` estiverem vazios — se a Wikipedia tiver o artigo, usa o resumo dela como sinopse provisória (com uma nota indicando a fonte, já que a licença exige atribuição) |
| **OMDb API** (`omdbapi.com`) | Sinopse + elenco + nota, mas **exige `imdb_id`** | Grátis até 1.000 req/dia | Não resolve *esse* caso específico agora (sem `imdb_id` ainda), mas já está preparado no `.env.example` (`OMDB_API_KEY`) pra quando o IMDb criar a página — vale automatizar: assim que `movies:refresh-existing` conseguir um `imdb_id` novo do TMDB, disparar uma consulta OMDb como segunda fonte de sinopse |
| **Wikidata (`wikidata.org` API/SPARQL)** | Descrição curta +, às vezes, imagens de atores com licença aberta (Wikimedia Commons) | Grátis, sem key | Menos rico que Wikipedia em texto, mas as fotos de lá são sempre de uso liberado — útil como fallback de fotos de elenco pros casos (diferentes deste) em que o TMDB não tiver `profile_path` |
| **Sinopse do trailer oficial (descrição do YouTube)** | Quando já existe `trailer_url` (nosso caso: ainda não tem), a descrição do vídeo no YouTube geralmente tem um resumo oficial escrito pelo estúdio | Grátis (YouTube Data API, tem cota diária) | Qualidade variável (às vezes é só um call-to-action, não uma sinopse de verdade) — usaria só como último fallback, não como fonte principal |

**Recomendação prática**: adicionar um fallback de sinopse via Wikipedia é o
item de maior custo-benefício — API pública, sem key, sem risco de ToS
(Wikipedia é explicitamente feita pra reuso, inclusive comercial, com
atribuição). Daria pra encaixar isso no mesmo job que já atualiza filmes
(`RefreshExistingMovies` ou um comando novo dedicado), rodando só quando
`synopsis` continuar vazia depois da tentativa normal no TMDB.

---

## Resumo executivo

| Necessidade | Ação recomendada | Fonte nova a integrar? |
|---|---|---|
| Pôster ausente | Aumentar frequência do refresh pra filmes `upcoming` sem pôster | Não (já cai sozinho quando TMDB atualizar) |
| Pôster ausente, TMDB nunca traz | Fallback em Fanart.tv | Sim (API grátis, key própria) |
| Sinopse ausente | Fallback em Wikipedia (`/page/summary`) | Sim (API grátis, sem key) |
| Sinopse ausente, IMDb já existe | Fallback em OMDb (`OMDB_API_KEY`, já no `.env.example`) | Não (já suportado, só falta acionar) |
| Fotos de elenco ausentes (caso raro, diferente do exemplo) | Fallback em Wikidata/Wikimedia Commons | Sim (API grátis, sem key) |

Nenhuma das fontes recomendadas exige scraping de site sem API — todas têm
endpoint público oficial. Não avaliei favoravelmente sites que exigiriam
scraping (IMPAwards, IMDb direto, Google Images), tanto pelo risco de ToS
quanto pela fragilidade de manutenção.

Se quiser, posso implementar qualquer um desses fallbacks como um novo Job
(seguindo o mesmo padrão dos `ProcessMovie*Job` que já existem) — me diga
qual prioridade (pôster via Fanart.tv, sinopse via Wikipedia, ou ambos) que eu
desenho e implemento.
