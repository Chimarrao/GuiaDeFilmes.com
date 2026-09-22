<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Corrige filmes que ficaram salvos com o título original (não traduzido)
 * em vez do título em pt-BR — acontece quando algum ponto do pipeline de
 * importação não pediu a tradução certa ao TMDB (ex: "War 2" em vez de
 * "Guerra 2"). Só troca se o título atual bate com o original_title E existe
 * uma tradução pt-BR diferente disponível — não mexe em filmes já corretos.
 *
 * Dispare via "php artisan movies:queue-title-fix".
 */
class FixMovieTitleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
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
            return;
        }

        $response = Http::timeout(15)->retry(2, 500)->get("https://api.themoviedb.org/3/movie/{$movie->tmdb_id}", [
            'api_key' => $apiKey,
            'language' => 'pt-BR',
        ]);

        if (!$response->successful()) {
            return;
        }

        $data = $response->json();
        $ptTitle = trim($data['title'] ?? '');
        $originalTitle = trim($data['original_title'] ?? '');

        if (
            $ptTitle !== ''
            && $ptTitle !== $originalTitle
            && trim($movie->title) === $originalTitle
        ) {
            $movie->update(['title' => $ptTitle]);
        }
    }
}
