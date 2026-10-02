<?php

use App\Models\Movie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------------
// GET /api/movies
// ---------------------------------------------------------------------

it('lista filmes paginados', function () {
    Movie::factory()->count(3)->create();

    $response = $this->getJson('/api/movies');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

it('filtra a listagem por status', function () {
    Movie::factory()->create(['status' => 'upcoming']);
    Movie::factory()->create(['status' => 'released']);

    $response = $this->getJson('/api/movies?status=upcoming');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.status'))->toBe('upcoming');
});

// ---------------------------------------------------------------------
// GET /api/movies/search
// ---------------------------------------------------------------------

it('busca sem termo devolve lista vazia sem tocar no banco', function () {
    $response = $this->getJson('/api/movies/search');

    $response->assertOk();
    $response->assertExactJson(['data' => []]);
});

it('busca com termo usa MATCH AGAINST — precisa de MySQL fora de transação', function () {
    // Além de precisar de MySQL (FULLTEXT é exclusivo do InnoDB/MyISAM), o
    // índice FULLTEXT do InnoDB não enxerga linhas inseridas e ainda não
    // comitadas na mesma transação (confirmado manualmente: MATCH AGAINST
    // devolve score 0 pra uma linha inserida na transação corrente, mesmo
    // um WHERE title LIKE achando ela). RefreshDatabase roda cada teste
    // dentro de uma transação que nunca comita (só dá rollback no final),
    // então esse endpoint especificamente não dá pra verificar de forma confiável
    // com a estratégia de isolamento de teste padrão — precisaria trocar
    // pra DatabaseMigrations (sem transação) só aqui, com truncate manual,
    // o que não compensa pra um endpoint de ordenação por relevância.
    $this->markTestSkipped(
        'FULLTEXT (InnoDB) não vê inserts não comitados da própria transação — RefreshDatabase usa transação por teste.'
    );
});

// ---------------------------------------------------------------------
// GET /api/movie/{slug}
// ---------------------------------------------------------------------

it('mostra os detalhes de um filme pelo slug', function () {
    $movie = Movie::factory()->create(['slug' => 'meu-filme-teste']);

    $response = $this->getJson('/api/movie/meu-filme-teste');

    $response->assertOk();
    $response->assertJsonPath('slug', 'meu-filme-teste');
    $response->assertJsonPath('id', $movie->id);
});

it('devolve 404 pra slug de filme inexistente', function () {
    $response = $this->getJson('/api/movie/nao-existe');

    $response->assertNotFound();
});

// ---------------------------------------------------------------------
// GET /api/movies/upcoming, /in-theaters, /released
// ---------------------------------------------------------------------

it('upcoming devolve 500 quando o cache de IDs não foi gerado', function () {
    $response = $this->getJson('/api/movies/upcoming');

    $response->assertStatus(500);
    $response->assertJsonStructure(['error']);
});

it('upcoming devolve os filmes na ordem do cache quando ele existe', function () {
    $movies = Movie::factory()->count(3)->create(['status' => 'upcoming']);
    $orderedIds = $movies->pluck('id')->reverse()->values()->all();

    Cache::put('upcoming_ids_v1', $orderedIds, 3600);
    Cache::put('upcoming_total_count', count($orderedIds), 3600);

    $response = $this->getJson('/api/movies/upcoming');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    expect($response->json('data.0.id'))->toBe($orderedIds[0]);
});

it('in-theaters devolve 500 quando o cache de IDs não foi gerado', function () {
    $response = $this->getJson('/api/movies/in-theaters');

    $response->assertStatus(500);
});

it('in-theaters devolve os filmes na ordem do cache quando ele existe', function () {
    $movies = Movie::factory()->count(2)->create(['status' => 'in_theaters']);
    $orderedIds = $movies->pluck('id')->values()->all();

    Cache::put('in_theaters_ids_v1', $orderedIds, 3600);
    Cache::put('in_theaters_total_count', count($orderedIds), 3600);

    $response = $this->getJson('/api/movies/in-theaters');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('released devolve 500 quando o cache de IDs não foi gerado', function () {
    $response = $this->getJson('/api/movies/released');

    $response->assertStatus(500);
});

it('released devolve os filmes na ordem do cache quando ele existe', function () {
    $movies = Movie::factory()->count(2)->create(['status' => 'released']);
    $orderedIds = $movies->pluck('id')->values()->all();

    Cache::put('released_ids_v1', $orderedIds, 3600);
    Cache::put('released_total_count', count($orderedIds), 3600);

    $response = $this->getJson('/api/movies/released');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

// ---------------------------------------------------------------------
// GET /api/movies/genre/{genre}, /decade/{decade}, /country/{code}
// Usam ORDER BY FIELD(...), exclusivo do MySQL — só a parte alcançável
// sem tocar nisso é testada aqui (erro de cache ausente / página vazia).
// ---------------------------------------------------------------------

it('byGenre devolve 500 quando o cache de IDs não foi gerado', function () {
    $response = $this->getJson('/api/movies/genre/acao');

    $response->assertStatus(500);
});

it('byGenre com ORDER BY FIELD — precisa de MySQL', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MovieController::byGenre() usa ORDER BY FIELD(), exclusivo do MySQL.');
    }

    $movie = Movie::factory()->create(['genres' => ['Ação']]);
    Cache::put('genre_acao_ids_v7', [$movie->id], 3600);

    $response = $this->getJson('/api/movies/genre/acao');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('byDecade devolve 500 quando o cache de IDs não foi gerado', function () {
    $response = $this->getJson('/api/movies/decade/2020s');

    $response->assertStatus(500);
});

it('byDecade com ORDER BY FIELD — precisa de MySQL', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MovieController::byDecade() usa ORDER BY FIELD(), exclusivo do MySQL.');
    }

    $movie = Movie::factory()->create(['release_date' => '2022-01-01']);
    Cache::put('decade_2020s_ids_v2', [$movie->id], 3600);

    $response = $this->getJson('/api/movies/decade/2020s');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('byCountry devolve 404 pra código de país desconhecido', function () {
    $response = $this->getJson('/api/movies/country/ZZ');

    $response->assertNotFound();
});

it('byCountry devolve 500 quando o cache de IDs não foi gerado (sem filtros)', function () {
    $response = $this->getJson('/api/movies/country/BR');

    $response->assertStatus(500);
});

it('byCountry com filtros ativos ignora o cache e consulta direto', function () {
    Movie::factory()->create([
        'production_countries' => ['Brazil'],
        'tmdb_rating' => 9.0,
    ]);
    Movie::factory()->create([
        'production_countries' => ['Brazil'],
        'tmdb_rating' => 2.0,
    ]);

    $response = $this->getJson('/api/movies/country/BR?minRating=5');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

// ---------------------------------------------------------------------
// GET /api/movies/filter
// ---------------------------------------------------------------------

it('filter sem filtro de gênero exclusivo consulta direto e ordena', function () {
    Movie::factory()->create(['title' => 'A', 'popularity' => 10]);
    Movie::factory()->create(['title' => 'B', 'popularity' => 90]);

    $response = $this->getJson('/api/movies/filter?sortBy=popularity');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect($response->json('data.0.title'))->toBe('B');
});

it('filter por minRating filtra corretamente', function () {
    Movie::factory()->create(['tmdb_rating' => 9.0]);
    Movie::factory()->create(['tmdb_rating' => 1.0]);

    $response = $this->getJson('/api/movies/filter?minRating=5');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('filter apenas com gênero devolve 500 sem cache carregado', function () {
    $response = $this->getJson('/api/movies/filter?genre=acao');

    $response->assertStatus(500);
});

it('filter combina gênero + ano + país + idioma + ordenação', function () {
    Movie::factory()->create([
        'genres' => ['Drama'],
        'release_date' => '2022-06-01',
        'production_countries' => [['iso_3166_1' => 'BR', 'name' => 'Brazil']],
        'original_language' => 'pt',
        'tmdb_rating' => 7.0,
    ]);
    Movie::factory()->create([
        'genres' => ['Ação'],
        'release_date' => '2010-01-01',
    ]);

    $response = $this->getJson('/api/movies/filter?genre=drama&yearFrom=2020&yearTo=2023&country=Brazil&language=pt&sortBy=rating');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

// ---------------------------------------------------------------------
// Paginação "além do cache" e página vazia (byGenre/byDecade/byCountry)
// ---------------------------------------------------------------------

it('byGenre devolve página vazia quando offset passa do total em cache', function () {
    Cache::put('genre_acao_ids_v7', [1, 2], 3600);

    $response = $this->getJson('/api/movies/genre/acao?page=5');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('byDecade devolve página vazia quando offset passa do total em cache', function () {
    Cache::put('decade_2020s_ids_v2', [1], 3600);

    $response = $this->getJson('/api/movies/decade/2020s?page=5');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('byDecade aceita um ano numérico direto (fallback fora do enum)', function () {
    $movie = Movie::factory()->create(['release_date' => '1905-01-01']);
    Cache::put('decade_1905_ids_v2', [$movie->id], 3600);

    $response = $this->getJson('/api/movies/decade/1905');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('byDecade devolve vazio pra um valor antes de 1850 (fora de qualquer intervalo)', function () {
    $response = $this->getJson('/api/movies/decade/1800');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('byCountry resolve país extinto', function () {
    Cache::put('country_SU_ids_v2', [1], 3600);

    $response = $this->getJson('/api/movies/country/SU');

    $response->assertOk();
});

it('byCountry devolve página vazia quando offset passa do total em cache', function () {
    Cache::put('country_BR_ids_v2', [1], 3600);

    $response = $this->getJson('/api/movies/country/BR?page=5');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

// ---------------------------------------------------------------------
// Contagem total ausente no cache (upcoming/in-theaters/released)
// ---------------------------------------------------------------------

it('upcoming devolve 500 quando só o cache de contagem total está faltando', function () {
    Cache::put('upcoming_ids_v1', [1], 3600);

    $response = $this->getJson('/api/movies/upcoming');

    $response->assertStatus(500);
});

it('in-theaters devolve 500 quando só o cache de contagem total está faltando', function () {
    Cache::put('in_theaters_ids_v1', [1], 3600);

    $response = $this->getJson('/api/movies/in-theaters');

    $response->assertStatus(500);
});

it('released devolve 500 quando só o cache de contagem total está faltando', function () {
    Cache::put('released_ids_v1', [1], 3600);

    $response = $this->getJson('/api/movies/released');

    $response->assertStatus(500);
});

// ---------------------------------------------------------------------
// Ordenação customizada consumida diretamente pela API (não só pelo cache:generate)
// ---------------------------------------------------------------------

// getCustomOrdering() lê a chave de cache "movie_ordering" (não a linha do
// banco direto) — esse caminho só é acionado se alguém popular essa chave
// especificamente. Nenhum comando do projeto faz isso hoje (cache:generate
// lê MovieOrdering::first() direto do banco pra montar upcoming_ids_v1 etc,
// que é o mecanismo que realmente roda em produção) — ou seja,
// paginateWithCustomOrdering() é código legado inalcançável no fluxo atual.
// Testado aqui simulando a chave de cache manualmente, só por completude.
it('upcoming mescla ordenação customizada com filmes automáticos quando a chave de cache "movie_ordering" existe', function () {
    $ordenado = Movie::factory()->create(['status' => 'upcoming', 'tmdb_id' => 301]);
    $automatico = Movie::factory()->create(['status' => 'upcoming', 'tmdb_id' => 302]);

    $ordering = \App\Models\MovieOrdering::first();
    $ordering->update(['upcoming' => [['id_tmdb' => 301, 'title' => $ordenado->title]]]);
    Cache::put('movie_ordering', $ordering->fresh());

    $response = $this->getJson('/api/movies/upcoming');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($ordenado->id);
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($automatico->id);
});

it('upcoming com ordenação customizada pagina corretamente além dos filmes ordenados', function () {
    $ordenado = Movie::factory()->create(['status' => 'upcoming', 'tmdb_id' => 401]);
    Movie::factory()->count(3)->create(['status' => 'upcoming']);

    $ordering = \App\Models\MovieOrdering::first();
    $ordering->update(['upcoming' => [['id_tmdb' => 401, 'title' => $ordenado->title]]]);
    Cache::put('movie_ordering', $ordering->fresh());

    $response = $this->getJson('/api/movies/upcoming?limit=1&page=2');

    $response->assertOk();
    expect($response->json('data.0.id'))->not->toBe($ordenado->id);
});

// ---------------------------------------------------------------------
// GET /api/movie/{slug} com reviews
// ---------------------------------------------------------------------

it('mostra as reviews processadas junto do filme', function () {
    $movie = Movie::factory()->create(['tmdb_id' => 555, 'slug' => 'filme-com-review-api']);
    \App\Models\Review::create([
        'movie_id' => 555,
        'review_tmdb' => [['author' => 'Crítico X', 'content' => 'Muito bom']],
    ]);

    $response = $this->getJson('/api/movie/filme-com-review-api');

    $response->assertOk();
    expect($response->json('reviews_data.0.author'))->toBe('Crítico X');
});

// ---------------------------------------------------------------------
// Páginas além do range cacheado (sem ordenação customizada) — cai na
// query direta "Páginas além do cache"
// ---------------------------------------------------------------------

it('upcoming cai pra query direta quando a página pedida passa do range cacheado', function () {
    $cached = Movie::factory()->create(['status' => 'upcoming']);
    $beyond = Movie::factory()->create(['status' => 'upcoming']);

    Cache::put('upcoming_ids_v1', [$cached->id], 3600);
    Cache::put('upcoming_total_count', 1, 3600);

    $response = $this->getJson('/api/movies/upcoming?limit=1&page=2');

    $response->assertOk();
});

it('released cai pra query direta quando a página pedida passa do range cacheado', function () {
    $cached = Movie::factory()->create(['status' => 'released', 'release_date' => now()->format('Y-m-d')]);
    Movie::factory()->create(['status' => 'released', 'release_date' => now()->format('Y-m-d')]);

    Cache::put('released_ids_v1', [$cached->id], 3600);
    Cache::put('released_total_count', 1, 3600);

    $response = $this->getJson('/api/movies/released?limit=1&page=2');

    $response->assertOk();
});

it('in-theaters cai pra query direta quando a página pedida passa do range cacheado', function () {
    $cached = Movie::factory()->create(['status' => 'in_theaters']);
    Movie::factory()->create(['status' => 'in_theaters']);

    Cache::put('in_theaters_ids_v1', [$cached->id], 3600);
    Cache::put('in_theaters_total_count', 1, 3600);

    $response = $this->getJson('/api/movies/in-theaters?limit=1&page=2');

    $response->assertOk();
});

it('inTheaters com ordenação customizada mescla filmes automáticos', function () {
    $ordenado = Movie::factory()->create(['status' => 'in_theaters', 'tmdb_id' => 601]);
    Movie::factory()->create(['status' => 'in_theaters', 'tmdb_id' => 602]);

    $ordering = \App\Models\MovieOrdering::first();
    $ordering->update(['in_theaters' => [['id_tmdb' => 601, 'title' => $ordenado->title]]]);
    Cache::put('movie_ordering', $ordering->fresh());

    $response = $this->getJson('/api/movies/in-theaters');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($ordenado->id);
});

// ---------------------------------------------------------------------
// filter() com yearFrom/yearTo isolados e com cache de "só gênero" preenchido
// ---------------------------------------------------------------------

it('filter por yearFrom/yearTo sozinhos (sem gênero) consulta direto', function () {
    Movie::factory()->create(['release_date' => '2021-01-01']);
    Movie::factory()->create(['release_date' => '2010-01-01']);

    $response = $this->getJson('/api/movies/filter?yearFrom=2020&yearTo=2022');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('filter apenas com gênero usa o cache de IDs quando ele existe', function () {
    $movie = Movie::factory()->create(['genres' => ['Ação']]);
    Cache::put('filter_genre_acao_ids_v1', [$movie->id], 3600);

    $response = $this->getJson('/api/movies/filter?genre=acao');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('filter apenas com gênero devolve página vazia quando offset passa do total em cache', function () {
    Cache::put('filter_genre_acao_ids_v1', [1], 3600);

    $response = $this->getJson('/api/movies/filter?genre=acao&page=5');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});
