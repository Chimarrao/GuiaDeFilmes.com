<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 8 de curiosidades (trivia): 87 filmes.
 *
 * Candidatos descobertos via GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (fonte real do catálogo em produção, ordenada por tmdb_vote_count desc),
 * filtrando fora todos os tmdb_ids já presentes em qualquer
 * database/data/movie_trivia*.json anterior. Cada tmdb_id abaixo foi copiado
 * diretamente do campo `tmdb_id` retornado por essa API (nenhum id foi
 * digitado de memória) e o `title` retornado foi conferido contra o filme
 * pretendido antes de escrever qualquer fato.
 *
 * Mistura ampla: terror/suspense moderno (M3GAN, [REC], Nosferatu 2024),
 * dramas e vencedores de Oscar/Cannes (A Vida dos Outros, A Hora Mais
 * Escura, Anatomia de uma Queda, O Segredo dos seus Olhos — cinema
 * argentino, Azul É A Cor Mais Quente), clássicos de Hitchcock (Festim
 * Diabólico, Disque M para Matar) e Kubrick (Glória Feita de Sangue),
 * filmografia de David Lynch, Wes Anderson, Paul Thomas Anderson e Oliver
 * Stone, grandes lançamentos recentes de franquia (Indiana Jones e a
 * Relíquia do Destino, The Flash, Gladiador 2, Aquaman 2, Quarteto
 * Fantástico: Primeiros Passos, Alien: Romulus, Um Lugar Silencioso: Dia
 * Um, Mickey 17, Uma Batalha Após a Outra, Pecadores) e comédias/clássicos
 * populares (Os Irmãos Cara de Pau, Trocando as Bolas, Scooby-Doo: O Filme,
 * Robin Hood: O Príncipe dos Ladrões).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_8.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_8: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
