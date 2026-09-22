<?php

namespace App\Console\Commands;

use App\Jobs\FixMovieTitleJob;
use App\Models\Movie;
use Illuminate\Console\Command;

/**
 * Enfileira jobs pra checar/corrigir filmes que ficaram com o título
 * original (não traduzido) em vez do título em pt-BR. Escopo limitado a
 * filmes de idioma original diferente de en/pt (onde a tradução errada
 * é visível/perceptível) pra não gastar chamadas de API à toa.
 */
class QueueMovieTitleFix extends Command
{
    protected $signature = 'movies:queue-title-fix
        {--limit=1000 : Quantidade máxima de filmes a enfileirar}';

    protected $description = 'Enfileira jobs pra corrigir filmes com título original em vez de traduzido pra pt-BR';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $ids = Movie::whereNotNull('tmdb_id')
            ->whereNotIn('original_language', ['en', 'pt'])
            ->orderByDesc('popularity')
            ->limit($limit)
            ->pluck('id');

        $bar = $this->output->createProgressBar($ids->count());
        foreach ($ids as $id) {
            try {
                FixMovieTitleJob::dispatch($id);
            } catch (\Throwable $e) {
                // Em QUEUE_CONNECTION=sync o job roda inline aqui mesmo —
                // uma falha não tratada dentro dele não pode derrubar o
                // resto do lote.
                $this->newLine();
                $this->warn("Falhou o filme id={$id}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info("Enfileirados {$ids->count()} jobs de correção de título.");

        return Command::SUCCESS;
    }
}
