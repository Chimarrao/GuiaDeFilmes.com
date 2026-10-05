<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 13 de curiosidades (trivia): 30 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API (ou pelo endpoint /api/movies/search quando usado
 * para checagem pontual) — nenhum id foi digitado de memória — e o `title`
 * retornado foi conferido contra o filme pretendido antes de escrever
 * qualquer fato.
 *
 * Cobertura: cinema de autor internacional e dramas premiados — Almodóvar
 * (A Pele Que Habito), Lars von Trier (Ninfomaníaca Vol. 1 e 2, A Casa Que
 * Jack Construiu), Xavier Dolan (Mommy), Gaspar Noé (Irreversível),
 * Iñárritu (Amores Brutos), Sofia Coppola (Maria Antonieta), Woody Allen
 * (Blue Jasmine), roteiro de Tarantino para Tony Scott (Amor à Queima-
 * Roupa), cinema indiano (3 Idiotas), iraniano (Persépolis), italiano
 * (Malèna), clássicos de Hollywood e Bond (A Ponte do Rio Kwai, As Pontes
 * de Madison, Serpico, dois filmes de 007), e biopics políticos premiados
 * (Selma, Milk, O Último Rei da Escócia, Hotel Ruanda, O Caso Richard
 * Jewell, A Morte de Stalin, Jogo de Espiões, Julie & Julia).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_13.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_13: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
