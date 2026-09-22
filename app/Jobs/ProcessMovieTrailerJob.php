<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Garante o trailer de um filme: reconfere no TMDB (pt-BR, depois en-US) se
 * existe um trailer oficial do YouTube. Se existir, usa ele (e libera um
 * trailer alternativo local que porventura já tenha sido baixado à toa). Se
 * não existir em nenhum idioma, baixa da fonte alternativa (só se ainda não
 * tiver nenhum trailer salvo).
 *
 * Substitui as antigas migrations de backfill de trailer — dispare via
 * "php artisan movies:queue-trailers".
 */
class ProcessMovieTrailerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;
    public int $backoff = 10;

    public function __construct(public int $movieId)
    {
    }

    public function handle(): void
    {
        $movie = Movie::find($this->movieId);

        if (!$movie || !$movie->tmdb_id) {
            return;
        }

        $apiKey = env('TMDB_API_KEY');
        if (!$apiKey) {
            Log::warning('ProcessMovieTrailerJob: TMDB_API_KEY não configurada.');
            return;
        }

        $youtubeUrl = $this->findYoutubeTrailerUrl((int) $movie->tmdb_id, $apiKey);

        if ($youtubeUrl) {
            if ($movie->imdb_trailer_url && str_contains($movie->imdb_trailer_url, '/trailers/')) {
                $localPath = $this->localPathFromUrl($movie->imdb_trailer_url);
                if ($localPath && file_exists($localPath)) {
                    unlink($localPath);
                }
            }

            $movie->update(['trailer_url' => $youtubeUrl, 'imdb_trailer_url' => null]);
            return;
        }

        // Já tem trailer local ou já não tem trailer_url mas está marcado
        // como tentado: não baixa de novo.
        if ($movie->trailer_url || $movie->imdb_trailer_url) {
            return;
        }

        $imdbId = $movie->external_ids['imdb_id'] ?? null;
        if (!$imdbId) {
            return;
        }

        $this->downloadAlternativeTrailer($movie, $imdbId);
    }

    private function downloadAlternativeTrailer(Movie $movie, string $imdbId): void
    {
        $destinationDir = public_path('trailers');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $url = "https://imdb.iamidiotareyoutoo.com/media/{$imdbId}";
        $cleanTitle = preg_replace('/[^a-zA-Z0-9.-]/', '', preg_replace('/\s+/', '-', $movie->title));
        $cleanTitle = substr($cleanTitle, 0, 50);
        $fileName = "{$imdbId}-{$cleanTitle}.mp4";
        $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

        try {
            $response = Http::withoutVerifying()->timeout(120)->sink($destinationPath)->get($url);

            if ($response->successful() && file_exists($destinationPath) && filesize($destinationPath) > 0) {
                $movie->update([
                    'imdb_trailer_url' => rtrim(config('app.url'), '/') . '/trailers/' . $fileName,
                ]);
                return;
            }
        } catch (\Throwable $e) {
            Log::warning("ProcessMovieTrailerJob: falha ao baixar trailer alternativo de {$movie->title}: {$e->getMessage()}");
        }

        if (file_exists($destinationPath)) {
            unlink($destinationPath);
        }
    }

    private function findYoutubeTrailerUrl(int $tmdbId, string $apiKey): ?string
    {
        $key = $this->fetchTrailerKey($tmdbId, $apiKey, 'pt-BR');

        if (!$key) {
            $key = $this->fetchTrailerKey($tmdbId, $apiKey, 'en-US');
        }

        return $key ? "https://www.youtube.com/watch?v={$key}" : null;
    }

    private function fetchTrailerKey(int $tmdbId, string $apiKey, string $language): ?string
    {
        try {
            $response = Http::timeout(15)->retry(2, 500)->get("https://api.themoviedb.org/3/movie/{$tmdbId}/videos", [
                'api_key' => $apiKey,
                'language' => $language,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $videos = collect($response->json('results') ?? [])
                ->filter(fn ($v) => ($v['site'] ?? null) === 'YouTube' && ($v['type'] ?? null) === 'Trailer');

            $official = $videos->firstWhere('official', true);

            return ($official ?? $videos->first())['key'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function localPathFromUrl(string $url): ?string
    {
        $fileName = basename(parse_url($url, PHP_URL_PATH) ?? '');

        return $fileName ? public_path('trailers/' . $fileName) : null;
    }
}
