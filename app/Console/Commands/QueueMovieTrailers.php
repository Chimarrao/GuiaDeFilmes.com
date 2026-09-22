<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMovieTrailerJob;
use App\Models\Movie;
use Illuminate\Console\Command;

/**
 * Substitui as antigas migrations de backfill de trailer (que rodavam tudo
 * de forma síncrona, travando o "migrate --force" por horas). Aqui só
 * enfileira um job por filme — o processamento de verdade roda no
 * queue-worker (com retry/backoff automáticos do Laravel).
 */
class QueueMovieTrailers extends Command
{
    protected $signature = 'movies:queue-trailers
        {--limit=1000 : Quantidade máxima de filmes a enfileirar}
        {--reclaim : Reconfere filmes que já têm trailer local (pra ver se agora tem no YouTube)}';

    protected $description = 'Enfileira jobs pra checar/baixar trailer dos filmes elegíveis';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $reclaim = (bool) $this->option('reclaim');

        $query = Movie::whereNotNull('tmdb_id');

        if ($reclaim) {
            $query->whereNotNull('imdb_trailer_url')
                ->where('imdb_trailer_url', 'like', '%/trailers/%')
                ->where(function ($q) {
                    $q->whereNull('trailer_url')->orWhere('trailer_url', '');
                });
        } else {
            $query->whereNull('imdb_trailer_url')
                ->where(function ($q) {
                    $q->whereNull('trailer_url')->orWhere('trailer_url', '');
                });
        }

        $ids = $query->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            ProcessMovieTrailerJob::dispatch($id);
        }

        $this->info("Enfileirados {$ids->count()} jobs de trailer" . ($reclaim ? ' (modo reclaim).' : '.'));

        return Command::SUCCESS;
    }
}
