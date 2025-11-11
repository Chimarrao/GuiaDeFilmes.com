<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Movie;

class WarmupExploreCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup-explore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-aquece o cache das páginas de explorar (gêneros, décadas, países)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Aquecendo cache das páginas de explorar...');
        
        // Gêneros
        $genres = ['acao', 'aventura', 'comedia', 'drama', 'ficcao-cientifica', 'terror', 'romance', 'suspense', 'animacao', 'crime'];
        $genreMap = [
            'acao' => 'Ação',
            'aventura' => 'Aventura',
            'comedia' => 'Comédia',
            'drama' => 'Drama',
            'ficcao-cientifica' => 'Ficção científica',
            'terror' => 'Terror',
            'romance' => 'Romance',
            'suspense' => 'Suspense',
            'animacao' => 'Animação',
            'crime' => 'Crime',
        ];
        
        $this->info('Aquecendo gêneros...');
        foreach ($genres as $genre) {
            $genreName = $genreMap[$genre];
            $cacheKey = "genre_{$genre}_ids_v6";
            
            Cache::remember($cacheKey, 7200, function () use ($genreName) {
                return Movie::whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%'])
                    ->where('tmdb_vote_count', '>=', 50)
                    ->whereNotNull('release_date')
                    ->orderByRaw('CAST(substr(release_date, 1, 4) AS UNSIGNED) DESC, tmdb_vote_count DESC')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            });
            
            $this->info("  ✓ {$genreName}");
        }
        
        // Décadas
        $decades = ['2020s', '2010s', '2000s', '1990s', '1980s', '1970s'];
        $decadeMap = [
            '2020s' => [2020, 2029],
            '2010s' => [2010, 2019],
            '2000s' => [2000, 2009],
            '1990s' => [1990, 1999],
            '1980s' => [1980, 1989],
            '1970s' => [1970, 1979],
        ];
        
        $this->info('Aquecendo décadas...');
        foreach ($decades as $decade) {
            [$startYear, $endYear] = $decadeMap[$decade];
            $cacheKey = "decade_{$decade}_ids_v2";
            
            Cache::remember($cacheKey, 7200, function () use ($startYear, $endYear) {
                return Movie::whereNotNull('release_date')
                    ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
                    ->where('tmdb_vote_count', '>=', 50)
                    ->orderBy('tmdb_vote_count', 'desc')
                    ->orderBy('popularity', 'desc')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            });
            
            $this->info("  ✓ {$decade}");
        }
        
        // Países
        $countries = [
            'BR' => 'Brazil',
            'US' => 'United States of America',
            'GB' => 'United Kingdom',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'JP' => 'Japan',
            'KR' => 'South Korea',
        ];
        
        $this->info('Aquecendo países...');
        foreach ($countries as $code => $name) {
            $cacheKey = "country_{$code}_ids_v2";
            
            Cache::remember($cacheKey, 7200, function () use ($name) {
                return Movie::whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($name) . '%'])
                    ->where('tmdb_vote_count', '>=', 50)
                    ->orderBy('tmdb_vote_count', 'desc')
                    ->orderBy('popularity', 'desc')
                    ->limit(200)
                    ->pluck('id')
                    ->toArray();
            });
            
            $this->info("  ✓ {$code} ({$name})");
        }
        
        $this->info('');
        $this->info('✅ Cache aquecido com sucesso!');
        
        return Command::SUCCESS;
    }
}
