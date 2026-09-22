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

    protected $description = 'Gera cache de páginas de filmes com troca atômica (zero downtime)';

    // Prefixo para cache temporário durante geração
    private const TEMP_PREFIX = 'TEMP_';
    
    // Array para armazenar estatísticas de cache
    private array $cacheStats = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Cache:Generate executado em ' . now());

        // Garantir que os diretórios de cache existam
        $this->ensureCacheDirectories();
        
        $this->info('🔥 Iniciando warmup de cache COMPLETO (com troca atômica)...');
        $this->newLine();
        
        // FASE 1: Gerar todos os caches com prefixo TEMP_
        $this->info('📝 FASE 1: Gerando caches temporários...');
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
        
        // ENDPOINT: /movies/filter?genre={genre} (cache de todos os gêneros)
        $this->warmupFilterGenres();
        
        // FASE 2: Trocar caches atomicamente (swap TEMP_ -> definitivo)
        $this->newLine();
        $this->info('⚡ FASE 2: Trocando caches atomicamente (zero downtime)...');
        $this->swapTempCaches();
        
        // FASE 3: Limpar caches antigos
        $this->newLine();
        $this->info('🧹 FASE 3: Limpando caches antigos...');
        $this->cleanupOldCaches();
        
        // FASE 4: Relatório final
        $this->newLine();
        $this->generateCacheReport();
        
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
     * - Cache de IDs pré-ordenados usando MovieOrdering (ILIMITADO - todos os filmes)
     * - Mescla filmes com ordenação customizada + filmes automáticos
     * - Cache de contagem total
     * - TTL: 86400s - aumentado para reduzir regenerações
     * - Usa prefixo TEMP_ durante geração para swap atômico
     */
    private function warmupUpcoming(): void
    {
        $this->info('📅 Gerando LANÇAMENTOS (Upcoming)...');
        
        // Cache de IDs pré-ordenados com prefixo TEMP_
        $tempKey = self::TEMP_PREFIX . "upcoming_ids_v1";
        $movieIds = Cache::remember($tempKey, 86400, function () {
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
                
                // Completar com TODOS os filmes automáticos (excluindo os já adicionados)
                $autoIds = Movie::where('status', 'upcoming')
                    ->whereNotIn('tmdb_id', $tmdbIds)
                    ->where('adult', 0)
                    ->orderBy('release_date', 'asc')
                    ->orderBy('popularity', 'desc')
                    ->pluck('id')
                    ->toArray();
                
                $finalIds = array_merge($finalIds, $autoIds);
            } else {
                // Sem ordenação customizada, buscar TODOS os filmes com ordenação automática
                $finalIds = Movie::where('status', 'upcoming')
                    ->where('adult', 0)
                    ->orderBy('release_date', 'asc')
                    ->orderBy('popularity', 'desc')
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total com prefixo TEMP_
        $tempCountKey = self::TEMP_PREFIX . 'upcoming_total_count';
        $totalCount = Cache::remember($tempCountKey, 86400, function () {
            return Movie::where('status', 'upcoming')->where('adult', 0)->count();
        });
        
        // Registrar estatísticas
        $this->trackCacheStats($tempKey, $movieIds);
        $this->trackCacheStats($tempCountKey, $totalCount);
        
        $this->info("  ✓ Cache: {$tempKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: {$tempCountKey}");
    }

    /**
     * Cache da página em cartaz (in theaters)
     * Endpoint: GET /movies/in-theaters
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados usando MovieOrdering (ILIMITADO - todos os filmes)
     * - Mescla filmes com ordenação customizada + filmes automáticos
     * - Cache de contagem total
     * - TTL: 86400s - aumentado para reduzir regenerações
     * - Usa prefixo TEMP_ durante geração para swap atômico
     */
    private function warmupInTheaters(): void
    {
        $this->info('🎬 Gerando EM CARTAZ (In Theaters)...');
        
        // Cache de IDs pré-ordenados com prefixo TEMP_
        $tempKey = self::TEMP_PREFIX . "in_theaters_ids_v1";
        $movieIds = Cache::remember($tempKey, 86400, function () {
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
                
                // Completar com TODOS os filmes automáticos (excluindo os já adicionados)
                $autoIds = Movie::where('status', 'in_theaters')
                    ->whereNotIn('tmdb_id', $tmdbIds)
                    ->where('adult', 0)
                    ->orderBy('release_date', 'desc')
                    ->orderBy('popularity', 'desc')
                    ->pluck('id')
                    ->toArray();
                
                $finalIds = array_merge($finalIds, $autoIds);
            } else {
                // Sem ordenação customizada, buscar TODOS os filmes com ordenação automática
                $finalIds = Movie::where('status', 'in_theaters')
                    ->where('adult', 0)
                    ->orderBy('release_date', 'desc')
                    ->orderBy('popularity', 'desc')
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total com prefixo TEMP_
        $tempCountKey = self::TEMP_PREFIX . 'in_theaters_total_count';
        $totalCount = Cache::remember($tempCountKey, 86400, function () {
            return Movie::where('status', 'in_theaters')->where('adult', 0)->count();
        });
        
        // Registrar estatísticas
        $this->trackCacheStats($tempKey, $movieIds);
        $this->trackCacheStats($tempCountKey, $totalCount);
        
        $this->info("  ✓ Cache: {$tempKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: {$tempCountKey}");
    }

    /**
     * Cache da página de lançados (released)
     * Endpoint: GET /movies/released
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados (ILIMITADO - todos os filmes)
     * - Cache de contagem total
     * - TTL: 86400s - dados mais estáveis
     * - Released normalmente não usa ordenação customizada, mas mantemos suporte
     * - Usa prefixo TEMP_ durante geração para swap atômico
     */
    private function warmupReleased(): void
    {
        $this->info('🎞️ Gerando LANÇADOS (Released)...');
        
        $currentYear = now()->year;
        
        // Cache de IDs pré-ordenados com prefixo TEMP_
        $tempKey = self::TEMP_PREFIX . "released_ids_v1";
        $movieIds = Cache::remember($tempKey, 86400, function () use ($currentYear) {
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
                
                // Completar com TODOS os filmes automáticos (excluindo os já adicionados)
                $autoIds = Movie::where('status', 'released')
                    ->whereNotIn('tmdb_id', $tmdbIds)
                    ->where('adult', 0)
                    ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                    ->pluck('id')
                    ->toArray();
                
                $finalIds = array_merge($finalIds, $autoIds);
            } else {
                // Sem ordenação customizada, buscar TODOS os filmes com ordenação automática
                $finalIds = Movie::where('status', 'released')
                    ->where('adult', 0)
                    ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                    ->pluck('id')
                    ->toArray();
            }
            
            return $finalIds;
        });
        
        // Cache de contagem total com prefixo TEMP_
        $tempCountKey = self::TEMP_PREFIX . 'released_total_count';
        $totalCount = Cache::remember($tempCountKey, 86400, function () use ($currentYear) {
            return Movie::whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?', [$currentYear - 2])
                ->whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?', [$currentYear])
                ->where('adult', 0)
                ->count();
        });
        
        // Registrar estatísticas
        $this->trackCacheStats($tempKey, $movieIds);
        $this->trackCacheStats($tempCountKey, $totalCount);
        
        $this->info("  ✓ Cache: {$tempKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: {$tempCountKey}");
    }

    /**
     * Cache de todos os gêneros
     * Endpoint: GET /movies/genre/{genre}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por gênero (ILIMITADO - todos os filmes por gênero)
     * - Usa JSON_CONTAINS para aproveitar índice idx_genres_json
     * - TTL: 86400s (2 horas)
     * - Usa enum GenreSlug para obter todos os gêneros
     * - Usa prefixo TEMP_ durante geração para swap atômico
     * 
     * IMPORTANTE: 
     * - Esta query usa JSON_CONTAINS que é otimizada para campos JSON
     * - Não confundir com genres_text (usado apenas para FULLTEXT search)
     */
    private function warmupGenres(): void
    {
        $this->info('🎭 Gerando GÊNEROS...');
        
        // Obtém todos os gêneros do enum GenreSlug
        $genreEnums = GenreSlug::cases();
        
        $total = count($genreEnums);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($genreEnums as $genreEnum) {
            $current++;
            $slug = $genreEnum->value;
            $name = $genreEnum->label();
            
            $tempKey = self::TEMP_PREFIX . "genre_{$slug}_ids_v7";
            
            try {
                $movieIds = Cache::remember($tempKey, 86400, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
                        ->where('adult', 0)
                        ->orderByRaw('release_year DESC, tmdb_vote_count DESC')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                
                // Registrar estatísticas
                $this->trackCacheStats($tempKey, $movieIds);
                
                $this->line("  ✓ [{$current}/{$total}] {$name}: {$count} filmes (cache: {$tempKey})");
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
     * - Cache de IDs por década (ILIMITADO - todos os filmes por década)
     * - Usa enum DecadeRange para todos os períodos
     * - TTL: 86400s (2 horas)
     * - Usa prefixo TEMP_ durante geração para swap atômico
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
            
            $tempKey = self::TEMP_PREFIX . "decade_{$slug}_ids_v2";
            
            try {
                $movieIds = Cache::remember($tempKey, 86400, function () use ($startYear, $endYear) {
                    return Movie::whereNotNull('release_date')
                        ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
                        ->where('adult', 0)
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                
                // Registrar estatísticas
                $this->trackCacheStats($tempKey, $movieIds);
                
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
     * - Cache de IDs por país (ILIMITADO - todos os filmes por país)
     * - Usa enum CountryCode para países modernos
     * - Usa getExtinctCountriesMap para países extintos
     * - TTL: 86400s (2 horas)
     * - Usa prefixo TEMP_ durante geração para swap atômico
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
            $tempKey = self::TEMP_PREFIX . "country_{$code}_ids_v2";
            
            try {
                $movieIds = Cache::remember($tempKey, 86400, function () use ($name) {
                    return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($name) . '%'])
                        ->where('adult', 0)
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                
                // Registrar estatísticas
                $this->trackCacheStats($tempKey, $movieIds);
                
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
     * - Usa prefixo TEMP_ durante geração para swap atômico
     */
    private function warmupCountriesList(): void
    {
        $this->info('🗺️ Gerando LISTA DE PAÍSES (com contagem)...');
        
        $tempKey = self::TEMP_PREFIX . 'countries_with_counts_v2';
        
        try {
            $countries = Cache::remember($tempKey, 86400, function () {
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
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Flag_of_the_German_Democratic_Republic.svg/500px-Flag_of_the_German_Democratic_Republic.svg.png'
                    ],
                    'Soviet Union' => [
                        'code' => 'SU',
                        'name' => 'União Soviética',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Flag_of_the_Soviet_Union.svg/500px-Flag_of_the_Soviet_Union.svg.png'
                    ],
                    'Yugoslavia' => [
                        'code' => 'YU',
                        'name' => 'Iugoslávia',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Flag_of_Yugoslavia_%281946-1992%29.svg/500px-Flag_of_Yugoslavia_%281946-1992%29.svg.png'
                    ],
                    'Serbia and Montenegro' => [
                        'code' => 'SAM',
                        'name' => 'Sérvia e Montenegro',
                        'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg/500px-Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg.png'
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
            
            // Registrar estatísticas
            $this->trackCacheStats($tempKey, $countries);
            
            $this->info("  ✓ Cache: {$tempKey}");
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
     * - Cache de IDs por gênero (ILIMITADO - todos os filmes)
     * - Chave: filter_genre_{slug}_ids_v1
     * - Ordenação: release_year DESC, tmdb_vote_count DESC
     * - TTL: 86400s (2 horas)
     * - Usa enum GenreSlug para obter todos os gêneros
     * - Usa prefixo TEMP_ durante geração para swap atômico
     * 
     * DIFERENÇA vs /movies/genre/{genre}:
     * - /movies/genre/{genre} usa JSON_CONTAINS (index idx_genres_json)
     * - /movies/filter?genre={genre} também usa JSON_CONTAINS, mas cache separado para filter
     */
    private function warmupFilterGenres(): void
    {
        $this->info('🎬 Gerando FILTRO DE GÊNEROS (filter endpoint)...');
        
        // Obtém todos os gêneros do enum GenreSlug
        $genreEnums = GenreSlug::cases();
        
        $total = count($genreEnums);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($genreEnums as $genreEnum) {
            $current++;
            $slug = $genreEnum->value;
            $name = $genreEnum->label();
            
            $tempKey = self::TEMP_PREFIX . "filter_genre_{$slug}_ids_v1";
            
            try {
                $movieIds = Cache::remember($tempKey, 86400, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
                        ->where('adult', 0)
                        ->orderByRaw('release_year DESC, tmdb_vote_count DESC')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                
                // Registrar estatísticas
                $this->trackCacheStats($tempKey, $movieIds);
                
                $this->line("  ✓ [{$current}/{$total}] {$name}: {$count} filmes (cache: {$tempKey})");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$name}: ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} gêneros)");
    }

    /**
     * Registra estatísticas de uma chave de cache para relatório final
     */
    private function trackCacheStats(string $key, mixed $data): void
    {
        $serialized = serialize($data);
        $sizeBytes = strlen($serialized);
        
        $this->cacheStats[] = [
            'key' => $key,
            'size' => $sizeBytes,
        ];
    }

    /**
     * Troca atomicamente todos os caches TEMP_ para suas chaves definitivas
     * Esta operação é rápida e garante zero downtime
     */
    private function swapTempCaches(): void
    {
        $swapped = 0;
        
        foreach ($this->cacheStats as $stat) {
            $tempKey = $stat['key'];
            
            // Verifica se é uma chave TEMP_
            if (str_starts_with($tempKey, self::TEMP_PREFIX)) {
                $finalKey = substr($tempKey, strlen(self::TEMP_PREFIX));
                
                // Pega o valor do cache temporário
                $value = Cache::get($tempKey);
                
                if ($value !== null) {
                    // Define o cache definitivo com o mesmo TTL
                    Cache::put($finalKey, $value, 86400);
                    $swapped++;
                }
            }
        }
        
        $this->info("  ✓ {$swapped} caches trocados atomicamente");
    }

    /**
     * Remove todos os caches com prefixo TEMP_ após swap bem-sucedido
     */
    private function cleanupOldCaches(): void
    {
        $deleted = 0;
        
        foreach ($this->cacheStats as $stat) {
            $tempKey = $stat['key'];
            
            if (str_starts_with($tempKey, self::TEMP_PREFIX)) {
                Cache::forget($tempKey);
                $deleted++;
            }
        }
        
        $this->info("  ✓ {$deleted} caches temporários removidos");
    }

    /**
     * Gera relatório final com tabela de estatísticas de cache
     */
    private function generateCacheReport(): void
    {
        $this->info('📋 RELATÓRIO DE CACHE');
        $this->newLine();
        
        // Ordena por tamanho decrescente
        usort($this->cacheStats, fn($a, $b) => $b['size'] <=> $a['size']);
        
        // Cabeçalho da tabela
        $this->line('┌─────────────────────────────────────────────────────────┬──────────────┐');
        $this->line('│ Chave de Cache                                          │ Tamanho      │');
        $this->line('├─────────────────────────────────────────────────────────┼──────────────┤');
        
        $totalSize = 0;
        
        foreach ($this->cacheStats as $stat) {
            // Remove prefixo TEMP_ para exibição
            $displayKey = str_starts_with($stat['key'], self::TEMP_PREFIX) 
                ? substr($stat['key'], strlen(self::TEMP_PREFIX))
                : $stat['key'];
            
            $totalSize += $stat['size'];
            
            // Formata tamanho
            $size = $this->formatBytes($stat['size']);
            
            // Ajusta tamanho da chave (max 55 chars)
            if (strlen($displayKey) > 55) {
                $displayKey = substr($displayKey, 0, 52) . '...';
            }
            
            $this->line(sprintf('│ %-55s │ %12s │', $displayKey, $size));
        }
        
        // Rodapé com total
        $this->line('├─────────────────────────────────────────────────────────┼──────────────┤');
        $this->line(sprintf('│ %-55s │ %12s │', 'TOTAL (' . count($this->cacheStats) . ' chaves)', $this->formatBytes($totalSize)));
        $this->line('└─────────────────────────────────────────────────────────┴──────────────┘');
    }

    /**
     * Formata bytes em formato legível (KB, MB, GB)
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }
}
