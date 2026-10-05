<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 18 de curiosidades (trivia): 44 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 81-90 (faixa ~946-1126 de tmdb_vote_count). Excluindo todos os tmdb_ids já
 * presentes em qualquer database/data/movie_trivia*.json anterior. Cada
 * tmdb_id foi copiado diretamente do campo `tmdb_id` retornado pela API —
 * nenhum id foi digitado de memória.
 *
 * Cobertura variada: clássicos e cult (Levada da Breca, Planeta Proibido,
 * Os Homens Preferem as Loiras, Jules e Jim, Sem Novidade no Front,
 * Julgamento em Nuremberg, Carruagens de Fogo), cinema de arte internacional
 * (Filho de Saul, Toni Erdmann, A Fita Branca, Mar Adentro, Coco Antes de
 * Chanel, 8 Mulheres), Bruce Lee e Sergio Leone (A Fúria do Dragão, Meu Nome
 * é Ninguém), Miyazaki pré-Ghibli (Lupin III: O Castelo de Cagliostro),
 * franquias/sequências (Karatê Kid 4, Dirty Harry na Lista Negra, Os Três
 * Mosqueteiros 1993), musicais (Dreamgirls, Hairspray 2007, O Ursinho Pooh),
 * e diversos outros dramas, comédias e thrillers.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_18.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_18: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
