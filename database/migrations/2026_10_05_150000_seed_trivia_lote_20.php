<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 20 de curiosidades (trivia): 65 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 101-110 (faixa ~685-796 de tmdb_vote_count). Excluindo todos os tmdb_ids
 * já presentes em qualquer database/data/movie_trivia*.json anterior. Cada
 * tmdb_id foi copiado diretamente do campo `tmdb_id` retornado pela API —
 * nenhum id foi digitado de memória. Títulos ambíguos (ex: "Agarra-me se
 * Puderes", "Difícil de Matar", "A Mansão do Inferno") foram confirmados
 * cruzando gênero/país/ano retornados pela própria API antes de escrever
 * qualquer fato.
 *
 * Cobertura variada: cinema de arte e autores (Umberto D., O Conformista,
 * A Última Sessão de Cinema, O Anjo Exterminador, El Topo, O Exército das
 * Sombras, 1900, Gritos e Sussurros, A Aventura), Kurosawa/Jodorowsky (A
 * Fortaleza Escondida, Kagemusha, Duna de Jodorowsky, El Topo), Nouvelle
 * Vague e giallo (O Círculo Vermelho, A Mansão do Inferno de Dario
 * Argento), biopics e dramas históricos (Munique: No Limite da Guerra,
 * BlackBerry, Rudy), clássicos de Hollywood (Milagre na Rua 34, Os Melhores
 * Anos de Nossas Vidas, Inferno Nº 17, A Dama de Shanghai, Stalag 17),
 * ação/cult dos anos 80-90 (Outland, Supergirl, Silverado, Difícil de
 * Matar, Smokey and the Bandit) e diversos outros dramas, comédias e
 * curiosidades de bastidor.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_20.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_20: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
