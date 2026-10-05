<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 21 de curiosidades (trivia): 60 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 111-140 (faixa ~449-685 de tmdb_vote_count). Excluindo todos os tmdb_ids
 * já presentes em qualquer database/data/movie_trivia*.json anterior. Cada
 * tmdb_id foi copiado diretamente do campo `tmdb_id` retornado pela API —
 * nenhum id foi digitado de memória. Títulos ambíguos em português (ex:
 * "No Silêncio da Noite" = In a Lonely Place, "À Meia Luz" = Gaslight,
 * "Paixões em Fúria" = Key Largo, "O Rouxinol" = The Nightingale, "Asas da
 * Liberdade" = Birdy) foram confirmados via /api/movies/search cruzando
 * sinopse, gênero, país e ano antes de escrever qualquer fato.
 *
 * Cobertura variada: cinema de autor europeu/asiático (Bergman — trilogia
 * da fé e A Hora do Lobo —, Visconti, Bresson, Godard, Kurosawa, Kiarostami
 * — trilogia de Koker e Close-Up —, Bunuel, Dreyer, Tarkovsky, Wong
 * Kar-wai), noir clássico (In a Lonely Place, Gaslight, Key Largo, Point
 * Blank), westerns (El Dorado, How the West Was Won), musicais (Fiddler on
 * the Roof, Nine, Jesus Christ Superstar, New York New York, Arthur),
 * dramas de guerra (Cross of Iron, Michael Collins, Memphis Belle, Birdy),
 * cinema latino-americano (O Abraço da Serpente, O Clã), e animação
 * japonesa de autor (Metrópolis, Angel's Egg, Jin-Roh, Memories, Redline).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_21.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_21: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
