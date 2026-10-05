<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 6 de curiosidades (trivia): 8 clássicos do cinema brasileiro
 * (Tropa de Elite, Tropa de Elite 2, Central do Brasil, Carandiru,
 * Bacurau, O Auto da Compadecida, Dona Flor e Seus Dois Maridos,
 * Lisbela e o Prisioneiro).
 *
 * Os candidatos óbvios de clássicos internacionais (Matrix, O Iluminado,
 * Jaws, Jurassic Park, Titanic, O Poderoso Chefão, Pulp Fiction, Forrest
 * Gump, Interestelar, Blade Runner, Alien, A Lista de Schindler, Batman -
 * O Cavaleiro das Trevas, Apocalypse Now, Cidade de Deus) e as franquias
 * (Harry Potter, Star Wars, Marvel, Senhor dos Anéis) já estavam 100%
 * cobertos pelos lotes anteriores — confirmado tmdb_id a tmdb_id contra
 * database/data/movie_trivia*.json antes de escrever qualquer fato novo.
 *
 * Cada tmdb_id abaixo foi confirmado via
 * GET https://guiadefilmes.com/api/movies/search?q=<título> (catálogo
 * real do site, comparando o título exato do resultado) — nenhum id foi
 * digitado de memória. Os fatos foram checados contra fontes públicas
 * (Berlinale, Cannes, Oscar, Ancine/Filme B, Wikipedia) antes de entrar
 * aqui; ver .claude/skills/curiosidades-filmes/SKILL.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_6.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_6: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
