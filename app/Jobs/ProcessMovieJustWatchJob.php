<?php

namespace App\Jobs;

use App\Http\Controllers\JustWatchController;
use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Busca as plataformas de streaming (JustWatch) de um filme e grava em
 * justwatch_watch_info. Substitui as antigas migrations de backfill de
 * JustWatch — dispare via "php artisan movies:queue-justwatch".
 */
class ProcessMovieJustWatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(public int $movieId)
    {
    }

    public function handle(): void
    {
        $movie = Movie::find($this->movieId);

        $title = trim($movie->title ?? '');
        if (!$movie || $title === '') {
            return;
        }

        $request = new Request([
            'query' => $title,
            'release_date' => $movie->release_date,
        ]);

        $controller = new JustWatchController();
        $response = $controller->search($request);
        $data = json_decode($response->getContent(), true);

        if (isset($data['error'])) {
            Log::warning("ProcessMovieJustWatchJob: erro pra {$movie->title}: {$data['error']}");
            return;
        }

        $movie->update(['justwatch_watch_info' => $data['offers'] ?? $data]);
    }
}
