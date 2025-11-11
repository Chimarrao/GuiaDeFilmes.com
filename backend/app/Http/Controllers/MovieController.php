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
     * Busca filmes por termo com sistema de relevância em múltiplas etapas
     * 
     * Sistema de busca inteligente com pontuação de relevância:
     * 
     * ETAPA 1 - Busca case-insensitive em campos principais:
     * - Título começa com o termo (maior relevância) - Score 1
     * - Título contém o termo - Score 2  
     * - Título original começa com o termo - Score 3
     * - Título original contém o termo - Score 4
     * 
     * ETAPA 2 - Busca em campos secundários:
     * - Tagline (frase de efeito) - Score 5
     * - Sinopse - Score 6
     * 
     * ETAPA 3 - Busca em metadados JSON:
     * - Cast (elenco) - Score 7
     * - Crew (equipe) - Score 8
     * - Genres (gêneros) - Score 9
     * 
     * ETAPA 4 - Busca por palavras individuais (>= 3 caracteres):
     * - Divide o termo e busca cada palavra separadamente - Score 10
     * 
     * Todas as buscas usam LOWER() para garantir case-insensitivity,
     * resolvendo problemas com acentuação e maiúsculas/minúsculas.
     * 
     * Exemplo: "falcão" encontra "Falcão e o Soldado Invernal"
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

        // Normaliza o termo de busca - separa cada palavra
        $searchWords = explode(' ', $searchLower);

        $movies = Movie::where(function($query) use ($searchTerm, $searchLower, $searchWords) {
            // ETAPA 1: Busca no título (case-insensitive usando LOWER)
            $query->whereRaw("LOWER(title) LIKE ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(original_title) LIKE ?", ["%{$searchLower}%"]);
            
            // ETAPA 2: Busca em campos secundários
            $query->orWhereRaw("LOWER(synopsis) LIKE ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(tagline) LIKE ?", ["%{$searchLower}%"]);
            
            // ETAPA 3: Busca em campos JSON (cast, crew, genres)
            $query->orWhereRaw("LOWER(cast) LIKE ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(crew) LIKE ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(genres) LIKE ?", ["%{$searchLower}%"]);
            
            // ETAPA 4: Busca por cada palavra individualmente
            foreach ($searchWords as $word) {
                if (strlen($word) >= 3) { // Apenas palavras com 3+ caracteres
                    $query->orWhereRaw("LOWER(title) LIKE ?", ["%{$word}%"])
                          ->orWhereRaw("LOWER(original_title) LIKE ?", ["%{$word}%"]);
                }
            }
        })
        // Ordenação por relevância: quanto menor o número, mais relevante
        ->orderByRaw("
            CASE 
                WHEN LOWER(title) LIKE ? THEN 1
                WHEN LOWER(title) LIKE ? THEN 2
                WHEN LOWER(original_title) LIKE ? THEN 3
                WHEN LOWER(original_title) LIKE ? THEN 4
                WHEN LOWER(tagline) LIKE ? THEN 5
                WHEN LOWER(synopsis) LIKE ? THEN 6
                WHEN LOWER(cast) LIKE ? THEN 7
                WHEN LOWER(crew) LIKE ? THEN 8
                WHEN LOWER(genres) LIKE ? THEN 9
                ELSE 10
            END
        ", [
            "{$searchLower}%",      // Título começa com termo
            "%{$searchLower}%",     // Título contém termo
            "{$searchLower}%",      // Título original começa
            "%{$searchLower}%",     // Título original contém
            "%{$searchLower}%",     // Tagline
            "%{$searchLower}%",     // Sinopse
            "%{$searchLower}%",     // Cast
            "%{$searchLower}%",     // Crew
            "%{$searchLower}%",     // Genres
        ])
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
            
            $baseQuery = Movie::where('release_date', '>', now())
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
        
        // Comportamento padrão: sem ordenação customizada
        $limit = $request->input('limit', 20);
        $movies = Movie::where('release_date', '>', now())
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
            
            $baseQuery = Movie::whereBetween('release_date', $dateRange)
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
        
        // Comportamento padrão: sem ordenação customizada
        $limit = $request->input('limit', 20);
        $movies = Movie::whereBetween('release_date', [
                now()->subDays(30),
                now()
            ])
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
     * @see Esta rota NÃO usa ordenação customizada (ao contrário de upcoming/inTheaters),
     * 
     * @param \Illuminate\Http\Request $request Parâmetros limit (padrão 20) e page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function released(Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        $currentYear = now()->year;
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
        $cacheKey = "genre_{$genre}_ids_v6";
        $movieIds = Cache::remember($cacheKey, 7200, function () use ($genreName) {
            return Movie::whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%'])
                ->whereNotNull('release_date')
                ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                ->pluck('id')
                ->toArray();
        });
        
        // Paginação manual dos IDs
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Busca os filmes mantendo a ordem
        $movies = Movie::whereIn('id', $pageIds)
            ->get()
            ->sortBy(function($movie) use ($pageIds) {
                return array_search($movie->id, $pageIds);
            })
            ->values();
        
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
        $movieIds = Cache::remember($cacheKey, 7200, function () use ($startYear, $endYear) {
            return Movie::whereNotNull('release_date')
                ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
                ->orderBy('tmdb_vote_count', 'desc')
                ->orderBy('popularity', 'desc')
                ->limit(200)
                ->pluck('id')
                ->toArray();
        });
        
        // Paginação manual
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Busca os filmes mantendo a ordem
        $movies = Movie::whereIn('id', $pageIds)
            ->get()
            ->sortBy(function($movie) use ($pageIds) {
                return array_search($movie->id, $pageIds);
            })
            ->values();
        
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
     * Lista filmes filtrados por país de produção
     * 
     * Busca filmes cujo campo JSON `production_countries` contém o país especificado.
     * O código do país (ex: "BR", "US") é convertido para o nome completo em inglês
     * (ex: "Brazil", "United States of America") usando o enum CountryCode.
     * 
     * DOIS MODOS DE OPERAÇÃO:
     * 
     * @param string $countryCode Código ISO 3166-1 alpha-2 do país (ex: "BR", "US", "FR")
     * @param \Illuminate\Http\Request $request Parâmetros limit, page e filtros opcionais
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function byCountry($countryCode, Request $request)
    {
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // Converte código do país para nome completo usando enum
        $countryCode = strtoupper($countryCode);
        $countryEnum = CountryCode::tryFromCode($countryCode);
        
        if (!$countryEnum) {
            return response()->json(['error' => 'País não encontrado'], 404);
        }
        
        // Nome em inglês para TMDB
        $countryName = $countryEnum->fullName(); 
        
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
        $movieIds = Cache::remember($cacheKey, 7200, function () use ($countryName) {
            return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($countryName) . '%'])
                
                ->orderBy('tmdb_vote_count', 'desc')
                ->orderBy('popularity', 'desc')
                ->limit(200)
                ->pluck('id')
                ->toArray();
        });
        
        // Paginação manual
        $offset = ($page - 1) * $limit;
        $pageIds = array_slice($movieIds, $offset, $limit);
        
        // Busca os filmes mantendo a ordem
        $movies = Movie::whereIn('id', $pageIds)
            ->get()
            ->sortBy(function($movie) use ($pageIds) {
                return array_search($movie->id, $pageIds);
            })
            ->values();
        
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
        
        $limit = $request->input('limit', 20);
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
        $ordering = Cache::remember('movie_ordering', 3600, function () {
            return MovieOrdering::first();
        });
        
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
            
            $totalAuto = Cache::remember($cacheKey, $cacheTtl, function () use ($baseQuery) {
                return $baseQuery->count();
            });
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
            
            $totalAuto = Cache::remember($cacheKey, $cacheTtl, function () use ($baseQuery) {
                return $baseQuery->count();
            });
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