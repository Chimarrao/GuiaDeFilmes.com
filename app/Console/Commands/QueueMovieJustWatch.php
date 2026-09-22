<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMovieJustWatchJob;
use App\Models\Movie;
use Illuminate\Console\Command;

/**
 * Substitui as antigas migrations de backfill de JustWatch. Só enfileira um
 * job por filme — o processamento de verdade roda no queue-worker.
 */
class QueueMovieJustWatch extends Command
{
    protected $signature = 'movies:queue-justwatch
        {--limit=1000 : Quantidade máxima de filmes a enfileirar}
        {--empty : Reprocessa quem já tentou e ficou com resultado vazio, priorizando os mais populares}';

    protected $description = 'Enfileira jobs pra buscar plataformas de streaming (JustWatch) dos filmes elegíveis';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $empty = (bool) $this->option('empty');

        $query = Movie::whereNotNull('tmdb_id')->where('adult', 0);

        if ($empty) {
            $query->whereNotNull('justwatch_watch_info')
                ->whereRaw('JSON_LENGTH(justwatch_watch_info) = 0')
                ->orderByDesc('popularity');
        } else {
            $query->whereNull('justwatch_watch_info');
        }

        $ids = $query->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            ProcessMovieJustWatchJob::dispatch($id);
        }

        $this->info("Enfileirados {$ids->count()} jobs de JustWatch" . ($empty ? ' (reprocessando vazios).' : '.'));

        return Command::SUCCESS;
    }
}
