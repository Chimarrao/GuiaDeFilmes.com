<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Popula curiosidades (trivia) pros filmes mais acessados do catálogo
 * (ranqueados por tmdb_vote_count, usado como proxy de "mais assistidos/
 * conhecidos" já que o projeto não rastreia pageviews por filme). Os fatos
 * em database/data/movie_trivia.json foram escritos manualmente, filme por
 * filme, com base em conhecimento público verificável (bilheteria, prêmios,
 * produção, elenco) — nenhum gerado automaticamente sem curadoria.
 *
 * Cobre 274 dos ~1000 filmes mais votados do catálogo nesta primeira leva
 * (priorizados pelos títulos mais famosos/confiáveis de verificar) — o
 * restante fica pra uma leva futura, usando o mesmo arquivo de dados como
 * base incremental.
 */
return new class extends Migration
{
    public function up(): void
    {

        $path = database_path('data/movie_trivia.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_for_popular_movies: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
