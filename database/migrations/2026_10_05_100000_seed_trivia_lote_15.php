<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 15 de curiosidades (trivia): 10 filmes.
 *
 * Mesma fonte de candidatos dos lotes 13-14: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória.
 *
 * Cobertura: animação e anime de autor — Satoshi Kon (Perfect Blue,
 * Paprika), Hideaki Anno (O Fim do Evangelho), Studio Ghibli (Sussurros do
 * Coração, O Reino dos Gatos, A Tartaruga Vermelha), Makoto Shinkai
 * (Suzume), Cartoon Saloon (Wolfwalkers), stop-motion australiano (Mary e
 * Max) e animação francesa indicada ao Oscar (Perdi Meu Corpo).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_15.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_15: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
