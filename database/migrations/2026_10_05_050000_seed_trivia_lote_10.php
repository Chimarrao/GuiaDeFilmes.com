<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 10 de curiosidades (trivia): 50 filmes.
 *
 * Mesma fonte de candidatos dos lotes 8 e 9: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória — e o `title`
 * retornado foi conferido contra o filme pretendido antes de escrever
 * qualquer fato.
 *
 * Cobertura: mais 5 filmes de James Bond da era clássica (Thunderball, Viva
 * e Deixe Morrer, Só Se Vive Duas Vezes, O Espião Que Me Amava, Moonraker),
 * clássicos mudos e do expressionismo alemão (Nosferatu 1922, M - O Vampiro
 * de Dusseldorf, Luzes da Cidade), Kubrick (Spartacus), Fellini (Oito e
 * Meio), Kurosawa (Rashomon), neorrealismo italiano (Ladrões de
 * Bicicleta), David Lynch (Eraserhead, A Estrada Perdida), Cronenberg
 * (Videodrome), Lars von Trier (Dogville), Spielberg (Munique), Scorsese
 * (O Rei da Comédia), Oliver Stone (JFK, Wall Street), Woody Allen
 * (Manhattan, Café Society), Paul Thomas Anderson (Vício Inerente,
 * Licorice Pizza), Wes Anderson (Asteroid City), Coen Brothers (Inside
 * Llewyn Davis), Miyazaki (O Menino e a Garça) e vencedores recentes de
 * Cannes/Oscar (Anora, Zona de Interesse).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_10.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_10: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
