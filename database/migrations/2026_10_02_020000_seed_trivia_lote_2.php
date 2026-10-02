<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Segunda leva de curiosidades (trivia), continuando
 * seed_trivia_for_popular_movies.php — mesmo critério de seleção
 * (tmdb_vote_count desc) e mesma regra de curadoria manual, sem geração
 * automática. Cobre 705 filmes adicionais do catálogo em
 * database/data/movie_trivia_lote_2.json (nenhum repetido do lote 1).
 *
 * A lista de candidatos veio de um cache local de top-1000 por vote_count
 * já coletado anteriormente, porque a API pública estava fora do ar (erro
 * 500) no momento da geração deste lote — por isso ficou em 705 em vez de
 * 1000: dos 726 filmes do top-1000 ainda não cobertos pelo lote 1, 21
 * foram pulados por falta de confiança numa curiosidade verificável, e o
 * restante da fila (posições 1001+) não pôde ser consultado com a API
 * fora do ar. Levas futuras podem complementar isso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $path = database_path('data/movie_trivia_lote_2.json');

        if (!file_exists($path)) {
            return;
        }

        $trivia = json_decode(file_get_contents($path), true);

        if (!is_array($trivia)) {
            return;
        }

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $bar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($trivia));
        $bar->setFormat('  %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('iniciando...');
        $bar->start();

        $updated = 0;

        foreach ($trivia as $tmdbId => $facts) {
            $movie = Movie::where('tmdb_id', (int) $tmdbId)->first();

            if ($movie) {
                $movie->update(['trivia' => $facts]);
                $updated++;
                $bar->setMessage("tmdb_id {$tmdbId} ({$movie->title})");
            } else {
                $bar->setMessage("tmdb_id {$tmdbId} (não encontrado)");
            }

            $bar->advance();
        }

        $bar->finish();
        $output->writeln('');

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_2: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
