<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 12 de curiosidades (trivia): 32 filmes.
 *
 * Mesma fonte de candidatos dos lotes 8-11: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória — e o `title`
 * retornado foi conferido contra o filme pretendido antes de escrever
 * qualquer fato.
 *
 * Cobertura: clássicos mudos e expressionismo alemão (O Gabinete do Dr.
 * Caligari), cinema soviético (Solaris de Tarkovsky, Vá e Veja), Bergman
 * (Morangos Silvestres), Kieślowski (A Liberdade é Azul), Kurosawa
 * (Yojimbo), Hitchcock (Pacto Sinistro), estreia de Christopher Nolan
 * (Following), estreia diretorial de Charlie Kaufman (Sinédoque, Nova
 * York) e Anomalisa, cinema indiano (RRR), coreano-americano (Minari),
 * italiano (The Hand of God, de Sorrentino), austríaco (Boa Noite, Mamãe!)
 * e dramas/dirigidos por nomes de peso (Oliver Stone, Kathryn Bigelow,
 * Jane Campion, John Carpenter, Clint Eastwood).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_12.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_12: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
