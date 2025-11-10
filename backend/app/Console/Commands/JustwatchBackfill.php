<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class JustwatchBackfill extends Command
{
    protected $signature = 'justwatch:backfill 
        {--start-id= : ID inicial} 
        {--limit= : Limite de filmes a processar}
        {--sleep=1 : Delay entre cada request em segundos}
        {--year= : Ano específico para buscar filmes}
        {--empty : Processa apenas filmes com JSON vazio []}';

    protected $description = 'Preenche o campo justwatch_watch_info para filmes onde está NULL ou vazio, chamando o endpoint local.';

    public function handle()
    {
        $startId = $this->option('start-id');
        $limit   = $this->option('limit');
        $sleep   = (int) $this->option('sleep');
        $year    = $this->option('year');
        $empty   = $this->option('empty');

        $this->info("== JustWatch Backfill Starting ==");
        $this->info("Start ID: " . ($startId ?: "NONE"));
        $this->info("Limit: " . ($limit ?: "ALL"));
        $this->info("Sleep: {$sleep}s");
        if ($year) {
            $this->info("Year Filter: {$year}");
        }
        if ($empty) {
            $this->info("Mode: Empty JSON only");
        }

        // Monta a query base
        $query = DB::table('movies')
            ->select('id', 'tmdb_id', 'title', 'release_date')
            ->orderBy('id', 'asc');

        // Se tem ano, filtra pelo ano e ordena nulls primeiro
        if ($year) {
            $query->whereYear('release_date', $year)
                  ->orderByRaw('CASE WHEN justwatch_watch_info IS NULL THEN 0 ELSE 1 END')
                  ->orderBy('id', 'asc');
        }
        // Se flag --empty, pega apenas os com JSON vazio
        elseif ($empty) {
            $query->where(function($q) {
                $q->where('justwatch_watch_info', '[]')
                  ->orWhere('justwatch_watch_info', 'null')
                  ->orWhere('justwatch_watch_info', '{}');
            });
        }
        // Caso padrão: apenas NULL
        else {
            $query->whereNull('justwatch_watch_info');
        }

        if ($startId) {
            $query->where('id', '>=', $startId);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $movies = $query->get();
        $total  = $movies->count();

        if ($total === 0) {
            $this->warn("Nenhum filme para processar.");
            return Command::SUCCESS;
        }

        $this->info("Total de filmes a processar: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($movies as $movie) {
            $title   = trim($movie->title ?? '');
            $releaseDate = $movie->release_date ?? null;
            $movieYear = $releaseDate ? substr($releaseDate, 0, 4) : 'N/A';
            
            $prefix = "[id={$movie->id} tmdb={$movie->tmdb_id}] \"{$title}\" ({$movieYear})";

            if ($title === '') {
                $this->warn("\n{$prefix} Sem título, ignorando.");
                $bar->advance();
                continue;
            }

            try {
                $params = ['query' => $title];
                
                // Adicionar release_date se disponível para melhor precisão
                if ($releaseDate) {
                    $params['release_date'] = $releaseDate;
                }

                $response = Http::timeout(20)->get('http://127.0.0.1:8000/api/justwatch/search', $params);

                if (!$response->successful()) {
                    $this->error("\n{$prefix} Erro HTTP: " . $response->status());
                    $bar->advance();
                    continue;
                }

                $data = $response->json();

                $offers = $data['offers'] ?? $data;

                DB::table('movies')
                    ->where('tmdb_id', $movie->tmdb_id)
                    ->limit(1)
                    ->update([
                        'justwatch_watch_info' => json_encode($offers, JSON_UNESCAPED_UNICODE),
                    ]);

                $count = is_array($offers) ? count($offers) : 1;
                $this->line("\n{$prefix} ✓ OK | {$count} offers");

            } catch (\Throwable $e) {
                $this->error("\n{$prefix} ERROR: " . $e->getMessage());
            }

            sleep($sleep);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n== JustWatch Backfill Completed ==");

        return Command::SUCCESS;
    }
}
