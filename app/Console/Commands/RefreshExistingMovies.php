<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RefreshExistingMovies extends Command
{
    protected $signature = 'movies:refresh-existing
        {--recent=1000 : Quantidade de filmes recentes/relevantes a atualizar}
        {--old=1000 : Quantidade de filmes antigos (amostra aleatória) a atualizar}
        {--years=3 : A partir de quantos anos atrás um filme é considerado "recente"}
        {--sleep=1 : Delay entre cada request em segundos}';

    protected $description = 'Atualiza filmes já existentes na base via TMDB: os mais relevantes dos últimos anos e uma amostra aleatória de filmes mais antigos';

    public function handle()
    {
        $recentCount = (int) $this->option('recent');
        $oldCount = (int) $this->option('old');
        $years = (int) $this->option('years');
        $sleep = (int) $this->option('sleep');

        $apiKey = env('TMDB_API_KEY');

        if (!$apiKey) {
            $this->error('TMDB_API_KEY não configurada no .env');
            return Command::FAILURE;
        }

        $cutoffDate = now()->subYears($years)->toDateString();

        $recentMovies = DB::table('movies')
            ->whereNotNull('tmdb_id')
            ->where('release_date', '>=', $cutoffDate)
            ->orderByDesc('popularity')
            ->limit($recentCount)
            ->get(['id', 'tmdb_id', 'title']);

        $oldMovies = DB::table('movies')
            ->whereNotNull('tmdb_id')
            ->where(function ($q) use ($cutoffDate) {
                $q->where('release_date', '<', $cutoffDate)->orWhereNull('release_date');
            })
            ->inRandomOrder()
            ->limit($oldCount)
            ->get(['id', 'tmdb_id', 'title']);

        $movies = $recentMovies->concat($oldMovies);
        $total = $movies->count();

        if ($total === 0) {
            $this->warn('Nenhum filme para atualizar.');
            return Command::SUCCESS;
        }

        $this->info('== Refresh de Filmes Existentes ==');
        $this->info("Recentes (>= {$cutoffDate}): {$recentMovies->count()} | Antigos: {$oldMovies->count()} | Total: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($movies as $movie) {
            try {
                $response = Http::timeout(30)->retry(3, 500)->get("https://api.themoviedb.org/3/movie/{$movie->tmdb_id}", [
                    'api_key' => $apiKey,
                    'language' => 'pt-BR',
                    'append_to_response' => 'videos,credits,images',
                ]);

                if (!$response->successful()) {
                    $this->error("\n[{$movie->tmdb_id}] {$movie->title}: HTTP {$response->status()}");
                    $bar->advance();
                    sleep($sleep);
                    continue;
                }

                $data = $response->json();

                $trailerUrl = $this->findYoutubeTrailerUrl($data['videos']['results'] ?? [], $movie->tmdb_id, $apiKey);

                DB::table('movies')->where('id', $movie->id)->update([
                    'title' => $data['title'] ?? $movie->title,
                    'trailer_url' => $trailerUrl,
                    'synopsis' => $data['overview'] ?? null,
                    'release_date' => $data['release_date'] ?? null,
                    'status' => $data['status'] ?? null,
                    'tmdb_rating' => $data['vote_average'] ?? null,
                    'tmdb_vote_count' => $data['vote_count'] ?? null,
                    'original_language' => $data['original_language'] ?? null,
                    'runtime' => $data['runtime'] ?? null,
                    'budget' => $data['budget'] ?? null,
                    'revenue' => $data['revenue'] ?? null,
                    'tagline' => $data['tagline'] ?? null,
                    'genres' => json_encode(collect($data['genres'] ?? [])->pluck('name')->values()),
                    'cast' => json_encode(collect($data['credits']['cast'] ?? [])->take(15)->map(fn ($a) => [
                        'id' => $a['id'],
                        'name' => $a['name'],
                        'character' => $a['character'] ?? null,
                        'profile_path' => !empty($a['profile_path']) ? "https://image.tmdb.org/t/p/w185{$a['profile_path']}" : null,
                    ])->values()),
                    'crew' => json_encode(collect($data['credits']['crew'] ?? [])
                        ->whereIn('job', ['Director', 'Writer', 'Producer'])
                        ->map(fn ($m) => [
                            'id' => $m['id'],
                            'name' => $m['name'],
                            'job' => $m['job'],
                            'department' => $m['department'] ?? null,
                        ])->values()),
                    'production_companies' => json_encode(collect($data['production_companies'] ?? [])->map(fn ($c) => ['name' => $c['name']])->values()),
                    'production_countries' => json_encode(collect($data['production_countries'] ?? [])->map(fn ($c) => ['name' => $c['name']])->values()),
                    'poster_url' => !empty($data['poster_path']) ? "https://image.tmdb.org/t/p/w500{$data['poster_path']}" : null,
                    'backdrop_url' => !empty($data['backdrop_path']) ? "https://image.tmdb.org/t/p/original{$data['backdrop_path']}" : null,
                    'adult' => !empty($data['adult']) ? 1 : 0,
                    'popularity' => $data['popularity'] ?? null,
                    'external_ids' => json_encode(['imdb_id' => $data['imdb_id'] ?? null]),
                    'updated_at' => now(),
                ]);

                $this->line("\n[{$movie->tmdb_id}] {$movie->title} ✓ atualizado");
            } catch (\Throwable $e) {
                $this->error("\n[{$movie->tmdb_id}] {$movie->title}: {$e->getMessage()}");
            }

            sleep($sleep);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n== Concluído ==");

        return Command::SUCCESS;
    }

    /**
     * Procura um trailer oficial do YouTube nos vídeos já obtidos (pt-BR).
     * Se não achar, faz uma segunda chamada pedindo em en-US antes de desistir —
     * a maioria dos trailers do TMDB só existe cadastrada em inglês.
     *
     * @param array $ptBrVideos Resultados de "videos" já vindos na resposta em pt-BR
     */
    private function findYoutubeTrailerUrl(array $ptBrVideos, int $tmdbId, string $apiKey): ?string
    {
        $key = $this->extractTrailerKey($ptBrVideos);

        if (!$key) {
            $response = Http::timeout(15)->retry(2, 500)->get("https://api.themoviedb.org/3/movie/{$tmdbId}/videos", [
                'api_key' => $apiKey,
                'language' => 'en-US',
            ]);

            if ($response->successful()) {
                $key = $this->extractTrailerKey($response->json('results') ?? []);
            }
        }

        return $key ? "https://www.youtube.com/watch?v={$key}" : null;
    }

    /**
     * Extrai a key do primeiro trailer oficial do YouTube numa lista de vídeos do TMDB.
     */
    private function extractTrailerKey(array $videos): ?string
    {
        $trailers = collect($videos)->filter(fn ($v) => ($v['site'] ?? null) === 'YouTube' && ($v['type'] ?? null) === 'Trailer');

        $official = $trailers->firstWhere('official', true);

        return ($official ?? $trailers->first())['key'] ?? null;
    }
}
