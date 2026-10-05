<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 17 de curiosidades (trivia): 84 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 71-80 (faixa ~1126-1346 de tmdb_vote_count), mais 3 filmes confirmados via
 * GET https://guiadefilmes.com/api/movies/search?q=<título> (Hannibal, a
 * Origem do Mal; Star Wars: A Guerra dos Clones; O Chacal). Excluindo todos
 * os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado pela API — nenhum id foi digitado de memória.
 *
 * Cobertura variada: clássicos de Hitchcock (A Sombra de uma Dúvida,
 * Marnie), De Palma (Vestida para Matar, A Dália Negra), Cronenberg
 * (Scanners, Crimes do Futuro), terror/suspense asiático (13 Assassinos,
 * Ichi o Assassino), cinema francês (Holy Motors, Albergue Espanhol, As
 * Diabólicas), cinema africano (Kiriku e a Feiticeira), noir/clássicos
 * (Sabrina, À Beira do Abismo, O Que Terá Acontecido a Baby Jane?),
 * franquias (Pânico 7, Fuga do Planeta dos Macacos, Mestres do Universo,
 * Distrito 13 Ultimato), biopics (Chaplin, Selena, Stan & Ollie, O Carteiro
 * e o Poeta) e diversos outros dramas e comédias cult.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_17.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_17: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
