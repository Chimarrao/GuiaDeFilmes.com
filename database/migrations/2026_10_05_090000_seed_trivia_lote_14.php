<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 14 de curiosidades (trivia): 28 filmes.
 *
 * Mesma fonte de candidatos do lote 13: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória.
 *
 * Cobertura: cinema contemporâneo de prestígio (2010s-2020s) — biopics
 * (Jobs, Casa Gucci, Napoleão, Mank), filmes premiados em festivais/Oscar
 * (A Sociedade da Neve, Druk, Ficção Americana, Flow), estreias de diretores
 * atores-virando-diretores (Creed III de Michael B. Jordan, tick tick BOOM
 * de Lin-Manuel Miranda, Rebel Moon de Zack Snyder), comédia/sátira (Borat
 * 2, Os Caras Malvados), documentários premiados (Free Solo, Cidadãoquatro,
 * Fahrenheit 11 de Setembro) e dramas históricos (42, Amistad).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_14.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_14: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
