<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 7 de curiosidades (trivia): 58 filmes.
 *
 * - 5 clássicos brasileiros ainda não cobertos (Que Horas Ela Volta?,
 *   Aquarius, O Pagador de Promessas, Bicho de Sete Cabeças, Cidade Baixa).
 * - 1 lançamento recente (Jurassic World: Recomeço, 2025).
 * - 52 clássicos internacionais muito conhecidos (vencedores de Oscar,
 *   filmes de Hitchcock, Kubrick, Scorsese, Kurosawa, Miyazaki, Chaplin,
 *   franquia James Bond, etc.) que ainda não estavam em nenhum
 *   database/data/movie_trivia*.json anterior.
 *
 * Harry Potter, O Senhor dos Anéis, Star Wars, Marvel, Jurassic Park/World
 * (exceto o lançamento de 2025), Missão Impossível e boa parte de Velozes
 * e Furiosos já estavam 100% cobertos pelos lotes anteriores — confirmado
 * filme a filme contra database/data/movie_trivia*.json antes de escrever
 * qualquer fato novo.
 *
 * Cada tmdb_id abaixo foi confirmado via
 * GET https://guiadefilmes.com/api/movies/search?q=<título> (ou, nos casos
 * em que a busca textual não retornava o filme, via
 * GET https://guiadefilmes.com/api/movies/filter?yearFrom=<ano>&yearTo=<ano>,
 * ex: "O Mágico de Oz" / tmdb_id 630) — catálogo real do site, comparando o
 * título exato do resultado. Nenhum id foi digitado de memória.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_7.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_7: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
