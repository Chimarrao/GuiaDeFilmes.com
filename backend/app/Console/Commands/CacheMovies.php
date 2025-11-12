<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Movie;
use App\Enums\{DecadeRange, CountryCode, GenreSlug};

class CacheMovies extends Command
{
    protected $signature = 'cache:generate';

    protected $description = 'Gera cache de páginas de filmes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Garantir que os diretórios de cache existam
        $this->ensureCacheDirectories();
        
        $this->info('🔥 Iniciando warmup de cache...');
        $this->newLine();
        
        // PÁGINA: Lançamentos (Upcoming)
        $this->warmupUpcoming();
        
        // PÁGINA: Em Cartaz (In Theaters)
        $this->warmupInTheaters();
        
        // PÁGINA: Lançados (Released)
        $this->warmupReleased();
        
        // PÁGINA: Gêneros
        $this->warmupGenres();
        
        // PÁGINA: Décadas
        $this->warmupDecades();
        
        // PÁGINA: Países
        $this->warmupCountries();
        
        $this->newLine();
        $this->info('✅ Cache aquecido com sucesso!');
        
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
     */
    private function warmupUpcoming(): void
    {
        $this->info('📅 Gerando LANÇAMENTOS (Upcoming)...');
        
        // Cache de contagem total
        $baseQuery = Movie::where('status', 'upcoming')
            ->orderBy('release_date', 'asc')
            ->orderBy('popularity', 'desc');
        
        $count = Cache::remember('upcoming_count', 300, function () use ($baseQuery) {
            return $baseQuery->count();
        });
        
        $this->info("  ✓ Total: {$count} filmes");
        $this->info("  ✓ Cache: upcoming_count");
    }

    /**
     * Cache da página em cartaz (in theaters)
     */
    private function warmupInTheaters(): void
    {
        $this->info('🎬 Gerando EM CARTAZ (In Theaters)...');
        
        // Cache de contagem total
        $dateRange = [now()->subDays(30), now()];
        $baseQuery = Movie::where('status', 'in_theaters')
            ->orderBy('release_date', 'desc')
            ->orderBy('popularity', 'desc');
        
        $count = Cache::remember('in_theaters_count', 300, function () use ($baseQuery) {
            return $baseQuery->count();
        });
        
        $this->info("  ✓ Total: {$count} filmes");
        $this->info("  ✓ Cache: in_theaters_count");
    }

    /**
     * Cache da página de lançados (released)
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
                ->pluck('id')
                ->toArray();
        });
        
        $total = count($movieIds);
        $this->info("  ✓ Total: {$total} filmes");
        $this->info("  ✓ Cache: {$cacheKey}");
    }

    /**
     * Cache de todos os gêneros usando enum GenreSlug
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
        
        foreach ($genres as $slug => $name) {
            $current++;
            $cacheKey = "genre_{$slug}_ids_v7";
            
            try {
                $movieIds = Cache::remember($cacheKey, 7200, function () use ($name) {
                    return Movie::whereRaw("JSON_CONTAINS(LOWER(genres), ?)", ['"' . strtolower($name) . '"'])
                        ->whereNotNull('release_date')
                        ->orderByRaw('release_year DESC, tmdb_vote_count DESC')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $this->info("  ✓ [{$current}/{$total}] {$name}: {$count} filmes");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$name}: ERRO - " . $e->getMessage());
            }
        }
    }

    /**
     * Cache de todas as décadas usando enum DecadeRange
     */
    private function warmupDecades(): void
    {
        $this->info('📆 Gerando DÉCADAS...');
        
        // Usa enum DecadeRange para obter todas as décadas
        $decades = DecadeRange::cases();
        $total = count($decades);
        $current = 0;
        
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
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $this->info("  ✓ [{$current}/{$total}] {$label} ({$slug}): {$count} filmes");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$label} ({$slug}): ERRO - " . $e->getMessage());
            }
        }
    }

    /**
     * Cache de todos os países usando enum CountryCode
     */
    private function warmupCountries(): void
    {
        $this->info('🌍 Gerando PAÍSES...');
        
        // Usa enum CountryCode para obter todos os países
        $countries = CountryCode::allFullNames();
        $total = count($countries);
        $current = 0;
        
        foreach ($countries as $code => $name) {
            $current++;
            $cacheKey = "country_{$code}_ids_v2";
            
            try {
                $movieIds = Cache::remember($cacheKey, 7200, function () use ($name) {
                    return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($name) . '%'])
                        ->orderBy('tmdb_vote_count', 'desc')
                        ->orderBy('popularity', 'desc')
                        ->pluck('id')
                        ->toArray();
                });
                
                $count = count($movieIds);
                $this->info("  ✓ [{$current}/{$total}] {$code} ({$name}): {$count} filmes");
            } catch (\Exception $e) {
                $this->error("  ✗ [{$current}/{$total}] {$code} ({$name}): ERRO - " . $e->getMessage());
            }
        }
    }
}
