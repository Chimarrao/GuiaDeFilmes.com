<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

return new class extends Migration
{
    /**
     * Espaço mínimo livre em disco a manter, em bytes (segurança contra lotar o disco).
     */
    private const MIN_FREE_DISK_BYTES = 20 * 1024 * 1024 * 1024; // 20GB

    /**
     * Versão corrigida da migration de backfill de trailer alternativo: antes de
     * baixar qualquer coisa, reconfere no TMDB (pt-BR e depois en-US) se o filme
     * tem mesmo um trailer oficial no YouTube. Se tiver, só grava o link (sem
     * baixar nada). Só baixa da fonte alternativa quem de fato não tem trailer
     * em nenhum dos dois idiomas.
     */
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $apiKey = env('TMDB_API_KEY');
        if (!$apiKey) {
            echo "  TMDB_API_KEY não configurada, abortando.\n";
            return;
        }

        $movies = DB::table('movies')
            ->whereNotNull('external_ids')
            ->whereNotNull('tmdb_id')
            ->whereNull('imdb_trailer_url')
            ->where(function ($query) {
                $query->whereNull('trailer_url')->orWhere('trailer_url', '');
            })
            ->select('id', 'tmdb_id', 'external_ids', 'title')
            ->get();

        $total = $movies->count();
        echo "  Total de filmes a reconferir/baixar: {$total}\n";

        $destinationDir = public_path('trailers');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $foundOnYoutube = 0;
        $downloaded = 0;
        $notFoundAnywhere = 0;
        $skipped = 0;
        $maxAttempts = 5;

        foreach ($movies as $movie) {
            $youtubeUrl = self::findYoutubeTrailerUrl((int) $movie->tmdb_id, $apiKey);

            if ($youtubeUrl) {
                DB::table('movies')->where('id', $movie->id)->update(['trailer_url' => $youtubeUrl]);
                $foundOnYoutube++;
                echo "  [ACHADO NO YOUTUBE] {$movie->title}\n";
                usleep(300000);
                continue;
            }

            if (disk_free_space(public_path()) <= self::MIN_FREE_DISK_BYTES) {
                echo "  Espaço em disco abaixo do mínimo de segurança, parando (rode de novo depois pra continuar).\n";
                break;
            }

            $externalIds = json_decode($movie->external_ids, true);
            $imdbId = $externalIds['imdb_id'] ?? null;

            if (!$imdbId) {
                $skipped++;
                continue;
            }

            $url = "https://imdb.iamidiotareyoutoo.com/media/{$imdbId}";
            $cleanTitle = preg_replace('/[^a-zA-Z0-9.-]/', '', preg_replace('/\s+/', '-', $movie->title));
            $cleanTitle = substr($cleanTitle, 0, 50);
            $fileName = "{$imdbId}-{$cleanTitle}.mp4";
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

            $success = false;
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withoutVerifying()->timeout(120)->sink($destinationPath)->get($url);

                    if ($response->successful() && file_exists($destinationPath) && filesize($destinationPath) > 0) {
                        $success = true;
                        break;
                    }

                    $lastError = "HTTP {$response->status()}";
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                }

                if ($attempt < $maxAttempts) {
                    sleep(2);
                }
            }

            if ($success) {
                $localUrl = rtrim(config('app.url'), '/') . '/trailers/' . $fileName;
                DB::table('movies')->where('id', $movie->id)->update(['imdb_trailer_url' => $localUrl]);
                $downloaded++;
                echo "  [BAIXADO ALTERNATIVO] {$movie->title}\n";
            } else {
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
                $notFoundAnywhere++;
                echo "  [SEM TRAILER EM LUGAR NENHUM] {$movie->title}: {$lastError}\n";
            }

            sleep(1);
        }

        echo "  Concluído: {$foundOnYoutube} achados no YouTube (sem download), {$downloaded} baixados do alternativo, {$notFoundAnywhere} sem trailer em lugar nenhum, {$skipped} sem IMDB ID.\n";
    }

    private static function findYoutubeTrailerUrl(int $tmdbId, string $apiKey): ?string
    {
        $key = self::fetchTrailerKey($tmdbId, $apiKey, 'pt-BR');

        if (!$key) {
            $key = self::fetchTrailerKey($tmdbId, $apiKey, 'en-US');
        }

        return $key ? "https://www.youtube.com/watch?v={$key}" : null;
    }

    private static function fetchTrailerKey(int $tmdbId, string $apiKey, string $language): ?string
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

    public function down(): void
    {
    }
};
