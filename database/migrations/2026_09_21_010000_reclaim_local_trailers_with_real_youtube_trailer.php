<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

return new class extends Migration
{
    /**
     * Corrige filmes que tiveram um trailer alternativo baixado pro disco local
     * sem necessidade: o motivo original era que o TMDB só foi consultado em
     * pt-BR, mas a maioria dos trailers só existe cadastrada em en-US. Aqui
     * re-checa cada um (pt-BR, depois en-US) e, se achar um trailer oficial do
     * YouTube de verdade, troca pra ele e apaga o arquivo local (libera disco).
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
            ->whereNotNull('imdb_trailer_url')
            ->where('imdb_trailer_url', 'like', '%/trailers/%')
            ->where(function ($q) {
                $q->whereNull('trailer_url')->orWhere('trailer_url', '');
            })
            ->select('id', 'tmdb_id', 'title', 'imdb_trailer_url')
            ->get();

        $total = $movies->count();
        echo "  Total de trailers locais a reconferir: {$total}\n";

        $reclaimed = 0;
        $keptLocal = 0;
        $bytesFreed = 0;

        foreach ($movies as $movie) {
            $youtubeUrl = self::findYoutubeTrailerUrl($movie->tmdb_id, $apiKey);

            if ($youtubeUrl) {
                $localPath = self::localPathFromUrl($movie->imdb_trailer_url);

                if ($localPath && file_exists($localPath)) {
                    $bytesFreed += filesize($localPath);
                    unlink($localPath);
                }

                DB::table('movies')->where('id', $movie->id)->update([
                    'trailer_url' => $youtubeUrl,
                    'imdb_trailer_url' => null,
                ]);

                $reclaimed++;
                echo "  [TROCADO P/ YOUTUBE] {$movie->title}\n";
            } else {
                $keptLocal++;
            }

            usleep(300000);
        }

        $freedMb = round($bytesFreed / 1024 / 1024, 1);
        echo "  Concluído: {$reclaimed} trocados pro YouTube (liberou ~{$freedMb}MB), {$keptLocal} continuam sem trailer no YouTube (mantidos locais).\n";
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

    private static function localPathFromUrl(string $url): ?string
    {
        $fileName = basename(parse_url($url, PHP_URL_PATH) ?? '');

        return $fileName ? public_path('trailers/' . $fileName) : null;
    }

    public function down(): void
    {
    }
};
