<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;
use App\Enums\{DecadeRange, CountryCode, GenreSlug};
use Illuminate\Support\Facades\Log;

class CacheMovies extends Command
{
    protected $signature = 'cache:generate';

    protected $description = 'Gera cache de páginas de filmes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Cache:Generate executado em ' . now());

        // Garantir que os diretórios de cache existam
        $this->ensureCacheDirectories();
        
        $this->info('🔥 Iniciando warmup de cache COMPLETO...');
        $this->newLine();
        
        // ENDPOINT: /movies/upcoming
        $this->warmupUpcoming();
        
        // ENDPOINT: /movies/in-theaters
        $this->warmupInTheaters();
        
        // ENDPOINT: /movies/released
        $this->warmupReleased();
        
        // ENDPOINT: /movies/genre/{genre}
        $this->warmupGenres();
        
        // ENDPOINT: /movies/decade/{decade}
        $this->warmupDecades();
        
        // ENDPOINT: /movies/country/{countryCode}
        $this->warmupCountries();
        
        // ENDPOINT: /countries (lista de países com contagem)
        $this->warmupCountriesList();
        
        // ENDPOINT: /movies/filter?genre={genre} (cache de todos os gêneros, até página 10)
        $this->warmupFilterGenres();
        
        $this->newLine();
        $this->info('✅ Cache completo gerado com sucesso!');
        $this->info('📊 Todos os endpoints principais estão otimizados!');
        
        return Command::SUCCESS;
    }

    /**
     * Garante que os diretórios de cache existam E tenham permissões corretas
     * 
     */
    private function ensureCacheDirectories(): void
    {
        $cachePath = storage_path('framework/cache/data');
        
        // Criar diretório raiz se não existir
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }
        
        // Ajustar permissões do diretório raiz (caso já exista)
        @chmod($cachePath, 0775);
        
        // Criar todos os subdiretórios possíveis (00-ff = 256 diretórios)
        // Isso garante que qualquer hash MD5 terá seu diretório
        $this->info('📁 Preparando estrutura de cache (256 diretórios)...');
        
        $hexChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
        $created = 0;
        $existing = 0;
        
        foreach ($hexChars as $first) {
            $firstDir = $cachePath . '/' . $first;
            
            if (!is_dir($firstDir)) {
                mkdir($firstDir, 0775, true);
            } else {
                @chmod($firstDir, 0775);
            }
            
            foreach ($hexChars as $second) {
                $secondDir = $firstDir . '/' . $second;
                
                if (!is_dir($secondDir)) {
                    mkdir($secondDir, 0775, true);
                    $created++;
                } else {
                    @chmod($secondDir, 0775);
                    $existing++;
                }
            }
        }
        
        $this->line("  ✓ Estrutura pronta: {$created} novos, {$existing} existentes (Total: 256)");
    }

    /**
     * Cache da página de lançamentos (upcoming)
     * Endpoint: GET /movies/upcoming
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados usando MovieOrdering (200 filmes = 10 páginas)
     * - Mescla filmes com ordenação customizada + filmes automáticos
     * - Cache de contagem total
     * - TTL: 86400s - aumentado para reduzir regenerações
     */
    private function warmupUpcoming(): void
    {
        $this->info('📅 Gerando LANÇAMENTOS (Upcoming)...');
        
        // Cache de IDs pré-ordenados
        $cacheKey = "upcoming_ids_v1";
        $movieIds = Cache::remember($cacheKey, 86400, function () {
            // Buscar ordenação customizada
            $ordering = \App\Models\MovieOrdering::first();
            $customOrder = $ordering ? ($ordering->upcoming ?? []) : [];
            
            $finalIds = [];
            
            // Se há ordenação customizada, adicionar primeiro
            if (!empty($customOrder)) {
                $tmdbIds = array_column($customOrder, 'id_tmdb');
                
                // Buscar IDs internos dos filmes com TMDB IDs customizados
                $customMovies = Movie::whereIn('tmdb_id', $tmdbIds)
                    ->where('status', 'upcoming')
                    ->where('adult', 0)
                    ->get();
                
                // Manter ordem do MovieOrdering
                foreach ($tmdbIds as $tmdbId) {
                    $movie = $customMovies->firstWhere('tmdb_id', $tmdbId);
                    if ($movie) {
                        $finalIds[] = $movie->id;
                    }
                }
                
                // Completar com filmes automáticos (excluindo os já adicionados)
                $remaining = 200 - count($finalIds);
                if ($remaining > 0) {
                    $autoIds = Movie::where('status', 'upcoming')
                        ->whereNotIn('tmdb_id', $tmdbIds)
                        ->where('adult', 0)
                        ->orderBy('release_date', 'asc')
                        ->orderBy('popularity', 'desc')
                        ->limit($remaining)
                        ->pluck('id')
                        ->toArray();
                    
                    $finalIds = array_merge($finalIds, $autoIds);
                }
            } else {
                // Sem ordenação customizada, usar apenas ordenação automática
                $finalIds = Movie::where('status', 'upcoming')
                    ->where('adult', 0)
                    ->orderBy('release_date', 'asc')
                    ->orderBy('popularity', 'desc')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('upcoming_total_count', 86400, function () {
            return Movie::where('status', 'upcoming')->where('adult', 0)->count();
        });
        
        $this->info("  ✓ Cache: {$cacheKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: upcoming_total_count");
    }

    /**
     * Cache da página em cartaz (in theaters)
     * Endpoint: GET /movies/in-theaters
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados usando MovieOrdering (200 filmes = 10 páginas)
     * - Mescla filmes com ordenação customizada + filmes automáticos
     * - Cache de contagem total
     * - TTL: 86400s - aumentado para reduzir regenerações
     */
    private function warmupInTheaters(): void
    {
        $this->info('🎬 Gerando EM CARTAZ (In Theaters)...');
        
        // Cache de IDs pré-ordenados
        $cacheKey = "in_theaters_ids_v1";
        $movieIds = Cache::remember($cacheKey, 86400, function () {
            // Buscar ordenação customizada
            $ordering = \App\Models\MovieOrdering::first();
            $customOrder = $ordering ? ($ordering->in_theaters ?? []) : [];
            
            $finalIds = [];
            
            // Se há ordenação customizada, adicionar primeiro
            if (!empty($customOrder)) {
                $tmdbIds = array_column($customOrder, 'id_tmdb');
                
                // Buscar IDs internos dos filmes com TMDB IDs customizados
                $customMovies = Movie::whereIn('tmdb_id', $tmdbIds)
                    ->where('status', 'in_theaters')
                    ->where('adult', 0)
                    ->get();
                
                // Manter ordem do MovieOrdering
                foreach ($tmdbIds as $tmdbId) {
                    $movie = $customMovies->firstWhere('tmdb_id', $tmdbId);
                    if ($movie) {
                        $finalIds[] = $movie->id;
                    }
                }
                
                // Completar com filmes automáticos (excluindo os já adicionados)
                $remaining = 200 - count($finalIds);
                if ($remaining > 0) {
                    $autoIds = Movie::where('status', 'in_theaters')
                        ->whereNotIn('tmdb_id', $tmdbIds)
                        ->where('adult', 0)
                        ->orderBy('release_date', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->limit($remaining)
                        ->pluck('id')
                        ->toArray();
                    
                    $finalIds = array_merge($finalIds, $autoIds);
                }
            } else {
                // Sem ordenação customizada, usar apenas ordenação automática
                $finalIds = Movie::where('status', 'in_theaters')
                    ->where('adult', 0)
                    ->orderBy('release_date', 'desc')
                    ->orderBy('popularity', 'desc')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('in_theaters_total_count', 86400, function () {
            return Movie::where('status', 'in_theaters')->where('adult', 0)->count();
        });
        
        $this->info("  ✓ Cache: {$cacheKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: in_theaters_total_count");
    }

    /**
     * Cache da página de lançados (released)
     * Endpoint: GET /movies/released
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados (200 filmes = 10 páginas)
     * - Cache de contagem total
     * - TTL: 86400s - dados mais estáveis
     * - Released normalmente não usa ordenação customizada, mas mantemos suporte
     */
    private function warmupReleased(): void
    {
        $this->info('🎞️ Gerando LANÇADOS (Released)...');
        
        $currentYear = now()->year;
        
        // Cache de IDs pré-ordenados
        $cacheKey = "released_ids_v1";
        $movieIds = Cache::remember($cacheKey, 86400, function () use ($currentYear) {
            // Buscar ordenação customizada (embora released raramente use)
            $ordering = \App\Models\MovieOrdering::first();
            $customOrder = $ordering ? ($ordering->released ?? []) : [];
            
            $finalIds = [];
            
            // Se há ordenação customizada, adicionar primeiro
            if (!empty($customOrder)) {
                $tmdbIds = array_column($customOrder, 'id_tmdb');
                
                // Buscar IDs internos dos filmes com TMDB IDs customizados
                $customMovies = Movie::whereIn('tmdb_id', $tmdbIds)
                    ->where('status', 'released')
                    ->where('adult', 0)
                    ->get();
                
                // Manter ordem do MovieOrdering
                foreach ($tmdbIds as $tmdbId) {
                    $movie = $customMovies->firstWhere('tmdb_id', $tmdbId);
                    if ($movie) {
                        $finalIds[] = $movie->id;
                    }
                }
                
                // Completar com filmes automáticos (excluindo os já adicionados)
                $remaining = 200 - count($finalIds);
                if ($remaining > 0) {
                    $autoIds = Movie::where('status', 'released')
                        ->whereNotIn('tmdb_id', $tmdbIds)
                        ->where('adult', 0)
                        ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                        ->limit($remaining)
                        ->pluck('id')
                        ->toArray();
                    
                    $finalIds = array_merge($finalIds, $autoIds);
                }
            } else {
                // Sem ordenação customizada, usar apenas ordenação automática
                $finalIds = Movie::where('status', 'released')
                    ->where('adult', 0)
                    ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('released_total_count', 86400, function () use ($currentYear) {
            return Movie::whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?', [$currentYear - 2])
                ->whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?', [$currentYear])
                ->where('adult', 0)
                ->count();
        });
        
        $this->info("  ✓ Cache: {$cacheKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: released_total_count");
    }

    /**
     * Cache de todos os gêneros
     * Endpoint: GET /movies/genre/{genre}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por gênero (até 200 filmes por gênero)
     * - Usa JSON_CONTAINS para aproveitar índice idx_genres_json
     * - TTL: 86400s (2 horas)
     * 
     * IMPORTANTE: 
     * - Esta query usa JSON_CONTAINS que é otimizada para campos JSON
     * - Não confundir com genres_text (usado apenas para FULLTEXT search)
     */
    private function warmupGenres(): void
    {
        $this->info('🎭 Gerando GÊNEROS...');
        
        // Usa enum GenreSlug para obter todos os gêneros
        $genres = [
            'acao' => 'Ação',
            'animacao' => 'Animação',
            'aventura' => 'Aventura',
            'comedia' => 'Comédia',
            'crime' => 'Crime',
            'documentario' => 'Documentário',
            'drama' => 'Drama',
            'familia' => 'Família',
            'fantasia' => 'Fantasia',
            'faroeste' => 'Faroeste',
            'ficcao-cientifica' => 'Ficção científica',
            'guerra' => 'Guerra',
            'historia' => 'História',
            'misterio' => 'Mistério',
            'musica' => 'Música',
            'romance' => 'Romance',
            'suspense' => 'Suspense',
            'terror' => 'Terror',
        ];
        
        $total = count($genres);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($genres as $slug => $name) {
            $current++;
            $cacheKey = "genre_{$slug}_ids_v7";
            
            try {
                $movieIds = Cache::remember($cacheKey, 86400, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
                        ->where('adult', 0)
                        ->orderByRaw('release_year DESC, tmdb_vote_count DESC')
                        ->limit(200)
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                $this->line("  ✓ [{$current}/{$total}] {$name}: {$count} filmes (cache: {$cacheKey})");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$name}: ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} gêneros)");
    }

    /**
     * Cache de todas as décadas
     * Endpoint: GET /movies/decade/{decade}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por década (até 200 filmes por década)
     * - Usa enum DecadeRange para todos os períodos
     * - TTL: 86400s (2 horas)
     */
    private function warmupDecades(): void
    {
        $this->info('📆 Gerando DÉCADAS...');
        
        // Usa enum DecadeRange para obter todas as décadas
        $decades = DecadeRange::cases();
        $total = count($decades);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($decades as $decadeEnum) {
            $current++;
            $slug = $decadeEnum->value;
            $label = $decadeEnum->label();
            [$startYear, $endYear] = $decadeEnum->range();
            
            $cacheKey = "decade_{$slug}_ids_v2";
            
            try {
                $movieIds = Cache::remember($cacheKey, 86400, function () use ($startYear, $endYear) {
                    return Movie::whereNotNull('release_date')
                        ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
                        ->where('adult', 0)
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->limit(200)
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                $this->line("  ✓ [{$current}/{$total}] {$label}: {$count} filmes ({$startYear}-{$endYear})");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$label} ({$slug}): ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} décadas)");
    }

    /**
     * Cache de todos os países (modernos + extintos)
     * Endpoint: GET /movies/country/{countryCode}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por país (até 200 filmes por país)
     * - Usa enum CountryCode para países modernos
     * - Usa getExtinctCountriesMap para países extintos
     * - TTL: 86400s (2 horas)
     */
    private function warmupCountries(): void
    {
        $this->info('🌍 Gerando PAÍSES...');
        
        // Países modernos (enum CountryCode)
        $modernCountries = CountryCode::allFullNames();
        
        // Países extintos (sincronizado com MovieController)
        $extinctCountries = [
            'CZE' => ['name' => 'Czechoslovakia'],
            'GDR' => ['name' => 'East Germany'],
            'SU' => ['name' => 'Soviet Union'],
            'YU' => ['name' => 'Yugoslavia'],
            'SAM' => ['name' => 'Serbia and Montenegro'],
            'AN' => ['name' => 'Netherlands Antilles'],
        ];
        
        // Combina os dois mapas
        $allCountries = $modernCountries;
        foreach ($extinctCountries as $code => $data) {
            $allCountries[$code] = $data['name'];
        }
        
        $total = count($allCountries);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($allCountries as $code => $name) {
            $current++;
            $cacheKey = "country_{$code}_ids_v2";
            
            try {
                $movieIds = Cache::remember($cacheKey, 86400, function () use ($name) {
                    return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($name) . '%'])
                        ->where('adult', 0)
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->limit(200)
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                
                // Identifica países extintos
                $extinct = isset($extinctCountries[$code]) ? ' [EXTINTO]' : '';
                $this->line("  ✓ [{$current}/{$total}] {$code} ({$name}): {$count} filmes{$extinct}");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$code} ({$name}): ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} países, incluindo " . count($extinctCountries) . " extintos)");
    }

    /**
     * Cache da lista de países com contagem de filmes (modernos + extintos)
     * Endpoint: GET /countries
     * 
     * OTIMIZAÇÃO:
     * - Cache da query completa de países
     * - Inclui mapeamento com CountryCode enum + países extintos
     * - TTL: 86400s - aumentado para estabilidade
     */
    private function warmupCountriesList(): void
    {
        $this->info('🗺️ Gerando LISTA DE PAÍSES (com contagem)...');
        
        $cacheKey = 'countries_with_counts_v2';
        
        try {
            $countries = Cache::remember($cacheKey, 86400, function () {
                $results = DB::select(<<<SQL
                    SELECT 
                        country,
                        COUNT(*) AS total_movies
                    FROM (
                        SELECT 
                            m.id AS movie_id,
                            JSON_UNQUOTE(JSON_EXTRACT(j.value, '$.name')) AS country
                        FROM movies m,
                             JSON_TABLE(m.production_countries, '$[*]' COLUMNS (
                                 value JSON PATH '$'
                             )) AS j
                        WHERE m.adult = 0
                    ) AS extracted
                    WHERE country IS NOT NULL AND country <> ''
                    GROUP BY country
                    ORDER BY total_movies DESC, country
                SQL);

                // Mapeamento de países extintos (sincronizado com CountryController)
                $extinctCountries = [
                    'Czechoslovakia' => [
                        'code' => 'CZE',
                        'name' => 'Tchecoslováquia',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/Flag_of_the_Czech_Republic.svg/1920px-Flag_of_the_Czech_Republic.svg.png'
                    ],
                    'East Germany' => [
                        'code' => 'GDR',
                        'name' => 'Alemanha Oriental',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Flag_of_the_German_Democratic_Republic.svg/2560px-Flag_of_the_German_Democratic_Republic.svg.png'
                    ],
                    'Soviet Union' => [
                        'code' => 'SU',
                        'name' => 'União Soviética',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Flag_of_the_Soviet_Union.svg/2560px-Flag_of_the_Soviet_Union.svg.png'
                    ],
                    'Yugoslavia' => [
                        'code' => 'YU',
                        'name' => 'Iugoslávia',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Flag_of_Yugoslavia_%281946-1992%29.svg/2560px-Flag_of_Yugoslavia_%281946-1992%29.svg.png'
                    ],
                    'Serbia and Montenegro' => [
                        'code' => 'SAM',
                        'name' => 'Sérvia e Montenegro',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg/2560px-Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg.png'
                    ],
                    'Netherlands Antilles' => [
                        'code' => 'AN',
                        'name' => 'Antilhas Holandesas',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg/1920px-Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg.png'
                    ],
                ];

                // Mapeia os resultados do banco com os dados do enum e países extintos
                $mapped = [];
                foreach ($results as $result) {
                    // IMPORTANTE: Verifica primeiro se é país extinto (prioridade)
                    if (isset($extinctCountries[$result->country])) {
                        $extinctData = $extinctCountries[$result->country];
                        $mapped[] = [
                            'code' => $extinctData['code'],
                            'name' => $extinctData['name'],
                            'flag' => $extinctData['flag'],
                            'count' => (int) $result->total_movies,
                            'extinct' => true,
                        ];
                    } else {
                        // Tenta mapear para país moderno pelo enum
                        $countryEnum = CountryCode::findByEnglishName($result->country);
                        
                        if ($countryEnum) {
                            $mapped[] = [
                                'code' => $countryEnum->value,
                                'name' => $countryEnum->label(),
                                'flag' => $countryEnum->getFlagUrl(),
                                'count' => (int) $result->total_movies,
                                'extinct' => false,
                            ];
                        }
                    }
                }

                return $mapped;
            });
            
            $total = count($countries);
            $totalExtinct = count(array_filter($countries, fn($c) => $c['extinct'] ?? false));
            $totalMovies = array_sum(array_column($countries, 'count'));
            
            $this->info("  ✓ Cache: {$cacheKey}");
            $this->info("  📊 {$total} países mapeados ({$totalExtinct} extintos), {$totalMovies} filmes catalogados");
        } catch (\Exception $e) {
            $this->error("  ✗ ERRO ao gerar cache de países: " . $e->getMessage());
        }
    }

    /**
     * Cache do endpoint /movies/filter?genre={genre}
     * Endpoint: GET /movies/filter?genre={genre}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por gênero (até 200 filmes = 10 páginas)
     * - Chave: filter_genre_{slug}_ids_v1
     * - Ordenação: release_year DESC, tmdb_vote_count DESC
     * - TTL: 86400s (2 horas)
     * 
     * DIFERENÇA vs /movies/genre/{genre}:
     * - /movies/genre/{genre} usa JSON_CONTAINS (index idx_genres_json)
     * - /movies/filter?genre={genre} também usa JSON_CONTAINS, mas cache separado para filter
     */
    private function warmupFilterGenres(): void
    {
        $this->info('🎬 Gerando FILTRO DE GÊNEROS (filter endpoint, até página 10)...');
        
        // Usa enum GenreSlug para obter todos os gêneros
        $genres = [
            'acao' => 'Ação',
            'animacao' => 'Animação',
            'aventura' => 'Aventura',
            'comedia' => 'Comédia',
            'crime' => 'Crime',
            'documentario' => 'Documentário',
            'drama' => 'Drama',
            'familia' => 'Família',
            'fantasia' => 'Fantasia',
            'faroeste' => 'Faroeste',
            'ficcao-cientifica' => 'Ficção científica',
            'guerra' => 'Guerra',
            'historia' => 'História',
            'misterio' => 'Mistério',
            'musica' => 'Música',
            'romance' => 'Romance',
            'suspense' => 'Suspense',
            'terror' => 'Terror',
        ];
        
        $total = count($genres);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($genres as $slug => $name) {
            $current++;
            $cacheKey = "filter_genre_{$slug}_ids_v1";
            
            try {
                $movieIds = Cache::remember($cacheKey, 86400, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
                        ->where('adult', 0)
                        ->orderByRaw('release_year DESC, tmdb_vote_count DESC')
                        ->limit(200) // 10 páginas × 20 itens/página
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                $this->line("  ✓ [{$current}/{$total}] {$name}: {$count} filmes (cache: {$cacheKey})");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$name}: ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} gêneros, 10 páginas cada)");
    }
}
