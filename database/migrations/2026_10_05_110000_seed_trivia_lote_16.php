<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 16 de curiosidades (trivia): 12 filmes.
 *
 * Mesma fonte de candidatos dos lotes 13-15: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória.
 *
 * Cobertura: terror clássico e marcos do gênero — Clive Barker
 * (Hellraiser), Rob Zombie (A Casa dos 1000 Corpos), Stuart Gordon
 * (Re-Animator), David Cronenberg (Crash: Estranhos Prazeres), franquias
 * (Gremlins 2, Pesadelo em Elm Street, Sexta-Feira 13, Massacre da Serra
 * Elétrica), e clássicos de linguagem cinematográfica com forte componente
 * de bastidor (Bonnie e Clyde, Acossado de Godard, Um Cão Andaluz de
 * Buñuel/Dalí, Hitchcock de 2012 sobre a produção de Psicose).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_16.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_16: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
