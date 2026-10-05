<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 23 de curiosidades (trivia): 107 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 141-180 (faixa ~333-449 de tmdb_vote_count), continuação do lote 22.
 * Excluindo todos os tmdb_ids já presentes em qualquer
 * database/data/movie_trivia*.json anterior. Cada tmdb_id foi copiado
 * diretamente do campo `tmdb_id` retornado pela API — nenhum id foi
 * digitado de memória.
 *
 * Duas divergências de título encontradas durante a curadoria:
 * - tmdb_id 11300 ("Totalmente Selvagem"): sinopse real é de "Something
 *   Wild" (Jonathan Demme, 1986), não "Wild at Heart" de David Lynch —
 *   confirmado que o título em português já salvo no site está correto
 *   pra esse filme, só a suposição inicial estava errada. Mantido no lote.
 * - tmdb_id 11617 ("Rio Bravo" no site): sinopse real é de "Rio Grande"
 *   (John Ford, 1950), não do clássico de Howard Hawks — o título salvo
 *   no site está ERRADO pra esse tmdb_id (bug de import pré-existente,
 *   não causado por este trabalho; o "Rio Bravo" de Hawks nem está no
 *   catálogo). Removido deste lote pra não publicar curiosidade correta
 *   (sobre Rio Grande) numa página com título errado (Rio Bravo) — isso
 *   ficaria visivelmente inconsistente pro visitante. Fica pendente de
 *   correção manual do título no banco antes de poder entrar num lote
 *   futuro.
 *
 * Cobertura variada: cinema de autor europeu (Pasolini — Mamma Roma,
 * Teorema, Evangelho Segundo São Mateus —, Buñuel, Godard, Fellini, Bergman,
 * Murnau, Erice, Béla Tarr), clássicos de Hollywood (Wings, Ninotchka, Rio
 * Grande, A Primeira Página), noir e thrillers de autor (Hitchcock —
 * Correspondente Estrangeiro, O Inquilino —, Brian De Palma), terror cult
 * (Lucio Fulci, Dagon, Candyman 2, A História de Ricky), documentários
 * musicais e de bastidores (Buena Vista Social Club, Stop Making Sense,
 * Hearts of Darkness/Apocalipse de Um Cineasta) e dramas biográficos
 * recentes (September 5, Queer, Wasp Network, Mãos de Pedra).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_23.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_23: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
