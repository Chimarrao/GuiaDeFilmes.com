<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieOrdering;
use App\Http\Resources\MovieListResource;
use App\Enums\{GenreSlug, DecadeRange, CountryCode, MovieSortBy};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    /**
     * Lista todos os filmes com paginação
     * 
     * Permite filtrar por status (upcoming, in_theaters, released).
     * Retorna 20 filmes por página por padrão.
     * 
     * @param Request $request Pode conter 'status' para filtrar
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Movie::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return MovieListResource::collection($query->paginate(20));
    }

    /**
     * Busca filmes usando MySQL FULLTEXT Search (ultrarrápida)
     * 
     * FUNCIONAMENTO:
     * - Usa índice FULLTEXT em (title, synopsis, tagline)
     * - NATURAL LANGUAGE MODE: busca palavras relevantes automaticamente
     * - Ordenação por score de relevância do MySQL (quanto maior, mais relevante)
     * - Desempate por popularidade do TMDB
     * 
     * @param Request $request Contém 'q' (termo de busca) e 'limit' (itens por página)
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('q', '');
        
        if (empty($searchTerm)) {
            $searchTerm = $request->query('q', '');
        }

        if (empty($searchTerm)) {
            return response()->json(['data' => []]);
        }

        $limit = $request->input('limit', 20);
        $searchLower = strtolower($searchTerm);

        $movies = Movie::where(function($query) use ($searchTerm, $searchLower) {
            // FULLTEXT em title, synopsis, tagline (ultrarrápido)
            $query->whereRaw(
                "MATCH(title, synopsis, tagline) AGAINST (? IN NATURAL LANGUAGE MODE)", 
                [$searchTerm]
            );
        })
        // Ordenação por relevância (score) calculada pelo MySQL
        ->orderByRaw(
            "MATCH(title, synopsis, tagline) AGAINST (? IN NATURAL LANGUAGE MODE) DESC", 
            [$searchTerm]
        )
        // Desempate por popularidade
        ->orderBy('popularity', 'desc')
        ->paginate($limit);

        return MovieListResource::collection($movies);
    }

    /**
     * Exibe detalhes completos de um filme
     * 
     * @param string $slug Slug único do filme (ex: "avatar-o-caminho-da-agua")
     * @return Movie Instância do filme com reviews processadas
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se filme não existir
     */
    public function show($slug)
    {
        $movie = Movie::with('reviews')->where('slug', $slug)->firstOrFail();
        
        // Processa as reviews para retornar os dados do JSON
        if ($movie->reviews->isNotEmpty()) {
            $allReviews = [];
            foreach ($movie->reviews as $review) {
                if (is_array($review->review_tmdb) && !empty($review->review_tmdb)) {
                    $allReviews = array_merge($allReviews, $review->review_tmdb);
                }
            }
            $movie->reviews_data = $allReviews;
        } else {
            $movie->reviews_data = [];
        }
        
        return $movie;
    }

    /**
     * Lista filmes que ainda serão lançados (upcoming)
     * 
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function upcoming(Request $request)
    {
        $customOrder = $this->getCustomOrdering('upcoming');
        
        // Se tem ordenação customizada, usar paginação mesclada
        if (!empty($customOrder)) {
            $tmdbIds = array_column($customOrder, 'id_tmdb');
            
            $baseQuery = Movie::where('status', 'upcoming')
                ->orderBy('release_date', 'asc')
                ->orderBy('popularity', 'desc');
            
            return $this->paginateWithCustomOrdering(
                $request, 
                $tmdbIds, 
                $baseQuery, 
                'upcoming_count',
                300
            );
        }
        
        // Comportamento padrão com cache
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        $cacheKey = "upcoming_ids_v1";
        $cachedIds = Cache::get($cacheKey);
        
        if (!$cachedIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        $offset = ($page - 1) * $limit;
        $totalCached = count($cachedIds);
        
        // Se a página está dentro do cache (páginas 1-10)
        if ($offset < $totalCached) {
            $pageIds = array_slice($cachedIds, $offset, $limit);
            
            // Busca filmes mantendo ordem do cache
            $movies = Movie::whereIn('id', $pageIds)
                ->get()
                ->sortBy(function($movie) use ($pageIds) {
                    return array_search($movie->id, $pageIds);
                })
                ->values();
            
            // Contagem total (cache)
            $totalCount = Cache::get('upcoming_total_count');
            
            if (!$totalCount) {
                return response()->json([
                    'error' => "Cache 'upcoming_total_count' não carregado. Execute o comando cache:generate."
                ], 500);
            }
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $movies,
                    $totalCount,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // Páginas além do cache: query direta
        $movies = Movie::where('status', 'upcoming')
            ->orderBy('release_date', 'asc')
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    /**
     * Lista filmes em cartaz nos cinemas (in theaters)
     * 
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function inTheaters(Request $request)
    {
        $customOrder = $this->getCustomOrdering('in_theaters');
        
        // Se tem ordenação customizada, usar paginação mesclada
        if (!empty($customOrder)) {
            $tmdbIds = array_column($customOrder, 'id_tmdb');
            $dateRange = [now()->subDays(30), now()];
            
            $baseQuery = Movie::where('status', 'in_theaters')
                ->orderBy('release_date', 'desc')
                ->orderBy('popularity', 'desc');
            
            return $this->paginateWithCustomOrdering(
                $request, 
                $tmdbIds, 
                $baseQuery, 
                'in_theaters_count',
                300
            );
        }
        
        // Comportamento padrão com cache
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // Cache de IDs pré-ordenados (200 filmes = 10 páginas)
        $cacheKey = "in_theaters_ids_v1";
        $cachedIds = Cache::get($cacheKey);
        
        if (!$cachedIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        $offset = ($page - 1) * $limit;
        $totalCached = count($cachedIds);
        
        // Se a página está dentro do cache (páginas 1-10)
        if ($offset < $totalCached) {
            $pageIds = array_slice($cachedIds, $offset, $limit);
            
            // Busca filmes mantendo ordem do cache
            $movies = Movie::whereIn('id', $pageIds)
                ->get()
                ->sortBy(function($movie) use ($pageIds) {
                    return array_search($movie->id, $pageIds);
                })
                ->values();
            
            // Contagem total (cache)
            $totalCount = Cache::get('in_theaters_total_count');
            
            if (!$totalCount) {
                return response()->json([
                    'error' => "Cache 'in_theaters_total_count' não carregado. Execute o comando cache:generate."
                ], 500);
            }
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $movies,
                    $totalCount,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // Páginas além do cache: query direta
        $movies = Movie::where('status', 'in_theaters')
            ->orderBy('release_date', 'desc')
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    /**
     * Lista filmes lançados nos últimos 2 anos (released)
     * 
     * Retorna filmes com ano de lançamento entre (ano_atual - 2) e ano_atual,
     * ordenados por ano (mais recentes primeiro) e depois por quantidade de votos (tmdb_vote_count).
     * 
     * OTIMIZAÇÃO COM CACHE:
     * - Usa cache de IDs pré-ordenados para primeiras 10 páginas (200 filmes)
     * - Cache key: released_ids_v1
     * - Paginação manual com array_slice para evitar query pesada
     * 
     * COMPORTAMENTO:
     * - Páginas 1-10: Usa cache (resposta instantânea < 20ms)
     * - Páginas 11+: Query direta no banco (resposta ~100ms)
     * 
     * @see Esta rota NÃO usa ordenação customizada (ao contrário de upcoming/inTheaters)
     * 
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function released(Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $currentYear = now()->year;
        
        // Cache de IDs pré-ordenados (200 filmes = 10 páginas)
        $cacheKey = "released_ids_v1";
        $cachedIds = Cache::get($cacheKey);
        
        if (!$cachedIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        $offset = ($page - 1) * $limit;
        $totalCached = count($cachedIds);
        
        // Se a página está dentro do cache (páginas 1-10)
        if ($offset < $totalCached) {
            $pageIds = array_slice($cachedIds, $offset, $limit);
            
            // Busca filmes mantendo ordem do cache
            $movies = Movie::whereIn('id', $pageIds)
                ->get()
                ->sortBy(function($movie) use ($pageIds) {
                    return array_search($movie->id, $pageIds);
                })
                ->values();
            
            // Contagem total (cache + banco)
            $totalCount = Cache::get('released_total_count');
            
            if (!$totalCount) {
                return response()->json([
                    'error' => "Cache 'released_total_count' não carregado. Execute o comando cache:generate."
                ], 500);
            }
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $movies,
                    $totalCount,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // Páginas além do cache: query direta
        $movies = Movie::whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?', [$currentYear - 2])
            ->whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?', [$currentYear])
            ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    /**
     * Lista filmes filtrados por gênero
     * 
     * Busca filmes que contêm o gênero especificado no campo JSON `genres`.
     * O slug do gênero (ex: "acao") é convertido para o nome completo (ex: "Ação")
     * usando o enum GenreSlug.
     * 
     * FILTROS APLICADOS:
     * - Ordenação: ano DESC (mais recentes primeiro), depois tmdb_vote_count DESC
     * 
     * @param string $genre Slug do gênero (ex: "acao", "ficcao-cientifica")
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function byGenre($genre, Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // Converte slug para nome do gênero usando enum
        $genreName = GenreSlug::toGenreName($genre);
        
        // Cache dos IDs por gênero (revalidado a cada 2 horas)
        // OTIMIZAÇÃO v7: Usa JSON_CONTAINS ao invés de LIKE para aproveitar idx_genres_json
        $cacheKey = "genre_{$genre}_ids_v7";
        $movieIds = Cache::get($cacheKey);
        
        if (!$movieIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        // Paginação manual dos IDs
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Se não há filmes nesta página, retornar vazio
        if (empty($pageIds)) {
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]),
                    count($movieIds),
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        $idsString = implode(',', $pageIds);
        $movies = Movie::whereIn('id', $pageIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
        
        $total = count($movieIds);
        
        return MovieListResource::collection(
            new \Illuminate\Pagination\LengthAwarePaginator(
                $movies,
                $total,
                $limit,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            )
        );
    }

    /**
     * Lista filmes filtrados por década
     * 
     * Busca filmes cujo ano de lançamento está dentro da década especificada.
     * O slug da década (ex: "2020s", "1990s") é convertido para intervalo de anos
     * usando o enum DecadeRange.
     * 
     * FILTROS APLICADOS:
     * - Ordenação: tmdb_vote_count DESC (mais votados primeiro), depois popularity DESC
     * - Limitado a 200 filmes (performance)
     * 
     * @param string $decade Slug da década (ex: "2020s", "1990s", "pre-1920") ou ano numérico
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function byDecade($decade, Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // Converte slug para intervalo de anos usando enum
        $decadeEnum = DecadeRange::tryFromValue($decade);
        
        if ($decadeEnum) {
            [$startYear, $endYear] = $decadeEnum->range();
        } else {
            // Fallback: tenta converter para número
            $startYear = intval($decade);

            // Data menor que o primeiro filme catalogado
            if ($startYear >= 1850) { 
                $endYear = $startYear + 9;
            } else {
                return MovieListResource::collection(collect());
            }
        }
        
        // Cache dos IDs por década (revalidado a cada 2 horas)
        $cacheKey = "decade_{$decade}_ids_v2";
        $movieIds = Cache::get($cacheKey);
        
        if (!$movieIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        // Paginação manual
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Se não há filmes nesta página, retornar vazio
        if (empty($pageIds)) {
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]),
                    count($movieIds),
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // OTIMIZAÇÃO: Usar FIELD() para manter ordem sem sortBy()
        $idsString = implode(',', $pageIds);
        $movies = Movie::whereIn('id', $pageIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
        
        $total = count($movieIds);
        
        return MovieListResource::collection(
            new \Illuminate\Pagination\LengthAwarePaginator(
                $movies,
                $total,
                $limit,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            )
        );
    }

    /**
     * Retorna mapeamento de países extintos/históricos
     * Sincronizado com extinctCountryFlags do frontend
     * 
     * @return array
     */
    private static function getExtinctCountriesMap(): array
    {
        return [
            'CZE' => ['name' => 'Czechoslovakia'],
            'GDR' => ['name' => 'East Germany'],
            'SU' => ['name' => 'Soviet Union'],
            'YU' => ['name' => 'Yugoslavia'],
            'SAM' => ['name' => 'Serbia and Montenegro'],
            'AN' => ['name' => 'Netherlands Antilles'],
        ];
    }

    /**
     * Lista filmes filtrados por país de produção
     * 
     * Busca filmes cujo campo JSON `production_countries` contém o país especificado.
     * O código do país (ex: "BR", "US") é convertido para o nome completo em inglês
     * (ex: "Brazil", "United States of America") usando o enum CountryCode.
     * 
     * Suporta tanto países modernos (via CountryCode enum) quanto países extintos
     * (via getExtinctCountriesMap).
     * 
     * @param string $countryCode Código ISO 3166-1 alpha-2 do país (ex: "BR", "US", "FR") ou código extinto (ex: "SU", "YU")
     * @param \Illuminate\Http\Request $request Parâmetros limit, page e filtros opcionais
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function byCountry($countryCode, Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        $countryCode = strtoupper($countryCode);
        $countryName = null;
        
        // Verifica primeiro se é país extinto (prioridade)
        $extinctCountries = self::getExtinctCountriesMap();
        if (isset($extinctCountries[$countryCode])) {
            $countryName = $extinctCountries[$countryCode]['name'];
        } else {
            // Se não é extinto, tenta enum de países modernos
            $countryEnum = CountryCode::tryFromCode($countryCode);
            
            if (!$countryEnum) {
                return response()->json(['error' => 'País não encontrado'], 404);
            }
            
            // Nome em inglês para TMDB
            $countryName = $countryEnum->fullName();
        } 
        
        // Quando há filtros, não usar cache (se não cache explodiria)
        $hasFilters = $request->filled('genre') || $request->filled('yearFrom') || $request->filled('yearTo') || $request->filled('minRating');
        
        // Query normal com filtros (sem cache)
        if ($hasFilters) {
            $query = Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($countryName) . '%']);
            
            if ($request->filled('genre')) {
                $genreName = GenreSlug::toGenreName($request->genre);
                $query->whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%']);
            }
            
            if ($request->filled('yearFrom')) {
                $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?", [intval($request->yearFrom)]);
            }
            
            if ($request->filled('yearTo')) {
                $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?", [intval($request->yearTo)]);
            }
            
            if ($request->filled('minRating')) {
                $query->where('tmdb_rating', '>=', floatval($request->minRating));
            }
            
            $movies = $query
                ->orderBy('tmdb_vote_count', 'desc')
                ->orderBy('popularity', 'desc')
                ->paginate($limit);
                
            return MovieListResource::collection($movies);
        }
        
        // Sem filtros: usa cache
        $cacheKey = "country_{$countryCode}_ids_v2";
        $movieIds = Cache::get($cacheKey);
        
        if (!$movieIds) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }
        
        // Paginação manual
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Se não há filmes nesta página, retornar vazio
        if (empty($pageIds)) {
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]),
                    count($movieIds),
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // OTIMIZAÇÃO: Usar FIELD() para manter ordem sem sortBy()
        $idsString = implode(',', $pageIds);
        $movies = Movie::whereIn('id', $pageIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
        
        $total = count($movieIds);
        
        return MovieListResource::collection(
            new \Illuminate\Pagination\LengthAwarePaginator(
                $movies,
                $total,
                $limit,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            )
        );
    }

    /**
     * Filtra filmes com múltiplos critérios combinados
     * 
     * Endpoint avançado que permite combinar vários filtros simultaneamente:
     * - Gênero (slug convertido via GenreSlug enum)
     * - Intervalo de anos (yearFrom, yearTo)
     * - Nota mínima (minRating)
     * - País de produção ÚNICO (JSON_LENGTH = 1)
     * - Idioma original (original_language)
     * 
     * @param \Illuminate\Http\Request $request Todos os filtros são opcionais
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function filter(Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // OTIMIZAÇÃO: Se apenas filtro de gênero é usado, usar cache (primeiras 10 páginas)
        $hasOnlyGenreFilter = $request->filled('genre') 
            && !$request->filled('yearFrom') 
            && !$request->filled('yearTo') 
            && !$request->filled('minRating') 
            && !$request->filled('country') 
            && !$request->filled('language');
        
        if ($hasOnlyGenreFilter) {
            $genreSlug = $request->genre;
            $cacheKey = "filter_genre_{$genreSlug}_ids_v1";
            
            // Ler IDs do cache (gerado pelo cache:generate)
            $cachedIds = Cache::get($cacheKey);
            
            if (!$cachedIds) {
                return response()->json([
                    'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
                ], 500);
            }
            
            // Paginação manual com IDs em cache (até página 10 = 200 filmes)
            $offset = ($page - 1) * $limit;
            $pageIds = array_slice($cachedIds, $offset, $limit);
            
            // Se não há filmes nesta página, retornar vazio
            if (empty($pageIds)) {
                return MovieListResource::collection(
                    new \Illuminate\Pagination\LengthAwarePaginator(
                        collect([]),
                        count($cachedIds),
                        $limit,
                        $page,
                        ['path' => $request->url(), 'query' => $request->query()]
                    )
                );
            }
            
            // Buscar filmes mantendo ordem do cache
            $idsString = implode(',', $pageIds);
            $movies = Movie::whereIn('id', $pageIds)
                ->orderByRaw("FIELD(id, {$idsString})")
                ->get();
            
            $total = count($cachedIds);
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $movies,
                    $total,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
        
        // Quando há múltiplos filtros, query direta (sem cache)
        $query = Movie::query();
        
        // Gênero filter usando enum
        if ($request->filled('genre')) {
            $genreName = GenreSlug::toGenreName($request->genre);
            $query->whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%']);
        }
        
        // Ano range filter
        if ($request->filled('yearFrom')) {
            $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?", [intval($request->yearFrom)]);
        }
        
        if ($request->filled('yearTo')) {
            $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?", [intval($request->yearTo)]);
        }
        
        // Nota filter
        if ($request->filled('minRating')) {
            $query->where('tmdb_rating', '>=', floatval($request->minRating));
        }
        
        // Country filter (apenas filmes com EXATAMENTE 1 país)
        if ($request->filled('country')) {
            $countryName = $request->country;
            
            $query->whereRaw("JSON_LENGTH(production_countries) = 1")->whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($countryName) . '%']);
        }
        
        // Idioma filter
        if ($request->filled('language')) {
            $query->where('original_language', $request->language);
        }
        
        // Sorting usando match expression + enum
        $sortByEnum = MovieSortBy::tryFromValue($request->input('sortBy', 'popularity')) ?? MovieSortBy::POPULARITY;
        
        $query->orderBy($sortByEnum->column(), $sortByEnum->direction());
        
        $movies = $query->paginate($limit);
        
        return MovieListResource::collection($movies);
    }

    /**
     * Obtém a ordenação customizada do cache/banco
     * 
     * @param string $type Tipo da ordenação: 'upcoming', 'in_theaters' ou 'released'
     * @return array Array de objetos com id_tmdb e position
     */
    private function getCustomOrdering(string $type): array
    {
        $ordering = Cache::get('movie_ordering');
        
        if (!$ordering) {
            // Se não há ordenação customizada, retornar vazio (não é erro crítico)
            return [];
        }
        
        return $ordering ? ($ordering->$type ?? []) : [];
    }

    /**
     * Cria paginação customizada mesclando filmes ordenados manualmente + filmes automáticos
     * 
     * @param \Illuminate\Http\Request $request Request com parâmetros limit/page
     * @param array $tmdbIds IDs dos filmes com ordenação customizada
     * @param \Illuminate\Database\Eloquent\Builder $baseQuery Query base para filmes automáticos
     * @param string $cacheKey Chave do cache para contagem total
     * @param int $cacheTtl TTL do cache em segundos
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function paginateWithCustomOrdering(
        Request $request, 
        array $tmdbIds, 
        $baseQuery, 
        string $cacheKey, 
        int $cacheTtl = 300
    ) {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;
        $orderedCount = count($tmdbIds);
        
        // Página ainda está nos filmes ordenados manualmente
        if ($offset < $orderedCount) {
            $pageIds = array_slice($tmdbIds, $offset, $limit);
            
            // Buscar filmes ordenados
            $orderedMovies = Movie::whereIn('tmdb_id', $pageIds)->get();
            $orderedMovies = $orderedMovies->sortBy(function($movie) use ($pageIds) {
                return array_search($movie->tmdb_id, $pageIds);
            })->values();
            
            // Completar página com filmes automáticos se necessário
            $remaining = $limit - $orderedMovies->count();
            if ($remaining > 0) {
                $additionalMovies = (clone $baseQuery)
                    ->whereNotIn('tmdb_id', $tmdbIds)
                    ->limit($remaining)
                    ->get();
                
                $orderedMovies = $orderedMovies->concat($additionalMovies);
            }
            
            $totalAuto = Cache::get($cacheKey);
            if (!$totalAuto) {
                // Fallback: se cache de contagem não existir, calcular diretamente
                $totalAuto = $baseQuery->count();
            }
            $total = $orderedCount + $totalAuto;
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $orderedMovies,
                    $total,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        } else {
            // Já passou dos filmes ordenados, usar apenas query automática
            $adjustedOffset = $offset - $orderedCount;
            
            $movies = (clone $baseQuery)
                ->whereNotIn('tmdb_id', $tmdbIds)
                ->skip($adjustedOffset)
                ->take($limit)
                ->get();
            
            $totalAuto = Cache::get($cacheKey);
            if (!$totalAuto) {
                // Fallback: se cache de contagem não existir, calcular diretamente
                $totalAuto = $baseQuery->count();
            }
            $total = $orderedCount + $totalAuto;
            
            return MovieListResource::collection(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    $movies,
                    $total,
                    $limit,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                )
            );
        }
    }
}