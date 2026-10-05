<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 22 de curiosidades (trivia): 60 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 111-140 (faixa ~449-685 de tmdb_vote_count), continuação do lote 21.
 * Excluindo todos os tmdb_ids já presentes em qualquer
 * database/data/movie_trivia*.json anterior (incluindo o próprio lote 21).
 * Cada tmdb_id foi copiado diretamente do campo `tmdb_id` retornado pela
 * API — nenhum id foi digitado de memória.
 *
 * Cobertura variada: continuação do cinema de autor europeu/asiático
 * (Buñuel — Os Esquecidos e Viridiana —, Argento, Bresson via Un Condamné
 * à Mort, Jean Vigo, Edward Yang, Teshigahara), dramas históricos sobre a
 * Segunda Guerra e pós-guerra (O Capitão, O Bombardeio), guerra civil e
 * conflitos regionais (Tangerinas, O Clã segue no lote 21), cinema latino-
 * americano e refugiados (As Nadadoras, O Insulto), clássicos de Hollywood
 * (Soberba de Orson Welles, Lifeboat de Hitchcock, Oliver!), anime de autor
 * (Kwaidan, A Mulher da Areia, Onibaba, Tampopo) e curtas fundadores da
 * Pixar (Luxo Jr., Knick Knack) e da Disney de guerra (Saludos Amigos,
 * Fun and Fancy Free).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_22.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_22: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
