<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
     * - Cache de IDs pré-ordenados (200 filmes = 10 páginas)
     * - Cache de contagem total
     * - TTL: 3600s (1 hora)
     */
    private function warmupUpcoming(): void
    {
        $this->info('📅 Gerando LANÇAMENTOS (Upcoming)...');
        
        // Cache de IDs pré-ordenados
        $cacheKey = "upcoming_ids_v1";
        $movieIds = Cache::remember($cacheKey, 3600, function () {
            return Movie::where('status', 'upcoming')
                ->orderBy('release_date', 'asc')
                ->orderBy('popularity', 'desc')
                ->limit(200)
                ->pluck('id')
                ->toArray();
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('upcoming_total_count', 3600, function () {
            return Movie::where('status', 'upcoming')->count();
        });
        
        $this->info("  ✓ Cache: {$cacheKey} ({$totalCount} filmes, " . count($movieIds) . " em cache)");
        $this->info("  ✓ Cache: upcoming_total_count");
    }

    /**
     * Cache da página em cartaz (in theaters)
     * Endpoint: GET /movies/in-theaters
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs pré-ordenados (200 filmes = 10 páginas)
     * - Cache de contagem total
     * - TTL: 3600s (1 hora)
     */
    private function warmupInTheaters(): void
    {
        $this->info('🎬 Gerando EM CARTAZ (In Theaters)...');
        
        // Cache de IDs pré-ordenados
        $cacheKey = "in_theaters_ids_v1";
        $movieIds = Cache::remember($cacheKey, 3600, function () {
            return Movie::where('status', 'in_theaters')
                ->orderBy('release_date', 'desc')
                ->orderBy('popularity', 'desc')
                ->limit(200)
                ->pluck('id')
                ->toArray();
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('in_theaters_total_count', 3600, function () {
            return Movie::where('status', 'in_theaters')->count();
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
     * - TTL: 7200s (2 horas) - dados mais estáveis
     */
    private function warmupReleased(): void
    {
        $this->info('🎞️ Gerando LANÇADOS (Released)...');
        
        $currentYear = now()->year;
        
        // Cache de IDs pré-ordenados
        $cacheKey = "released_ids_v1";
        $movieIds = Cache::remember($cacheKey, 7200, function () use ($currentYear) {
            return Movie::where('status', 'released')
                ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                ->limit(200)
                ->pluck('id')
                ->toArray();
        });
        
        // Cache de contagem total
        $totalCount = Cache::remember('released_total_count', 7200, function () use ($currentYear) {
            return Movie::whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?', [$currentYear - 2])
                ->whereRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?', [$currentYear])
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
     * - TTL: 7200s (2 horas)
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
            'tv-movie' => 'Filme de TV',
        ];
        
        $total = count($genres);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($genres as $slug => $name) {
            $current++;
            $cacheKey = "genre_{$slug}_ids_v7";
            
            try {
                $movieIds = Cache::remember($cacheKey, 7200, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
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
     * - TTL: 7200s (2 horas)
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
                $movieIds = Cache::remember($cacheKey, 7200, function () use ($startYear, $endYear) {
                    return Movie::whereNotNull('release_date')
                        ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
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
     * Cache de todos os países
     * Endpoint: GET /movies/country/{countryCode}
     * 
     * OTIMIZAÇÃO:
     * - Cache de IDs por país (até 200 filmes por país)
     * - Usa enum CountryCode para todos os países
     * - TTL: 7200s (2 horas)
     */
    private function warmupCountries(): void
    {
        $this->info('🌍 Gerando PAÍSES...');
        
        // Usa enum CountryCode para obter todos os países
        $countries = CountryCode::allFullNames();
        $total = count($countries);
        $current = 0;
        $totalMovies = 0;
        
        foreach ($countries as $code => $name) {
            $current++;
            $cacheKey = "country_{$code}_ids_v2";
            
            try {
                $movieIds = Cache::remember($cacheKey, 7200, function () use ($name) {
                    return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($name) . '%'])
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->limit(200)
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $totalMovies += $count;
                $this->line("  ✓ [{$current}/{$total}] {$code} ({$name}): {$count} filmes");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$code} ({$name}): ERRO - " . $e->getMessage());
            }
        }
        
        $this->info("  📊 Total: {$totalMovies} registros em cache ({$total} países)");
    }
}
