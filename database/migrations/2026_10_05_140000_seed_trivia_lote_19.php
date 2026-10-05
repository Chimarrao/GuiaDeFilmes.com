<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 19 de curiosidades (trivia): 54 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 91-100 (faixa ~796-946 de tmdb_vote_count). Excluindo todos os tmdb_ids já
 * presentes em qualquer database/data/movie_trivia*.json anterior. Cada
 * tmdb_id foi copiado diretamente do campo `tmdb_id` retornado pela API —
 * nenhum id foi digitado de memória.
 *
 * Cobertura variada: clássicos do cinema de arte europeu e soviético
 * (Roma, Cidade Aberta; A Grande Ilusão; Andrei Rublev; A Infância de Ivan;
 * Um Homem com uma Câmera; O Desprezo; A Dupla Vida de Véronique; Cléo das
 * 5 às 7), Hollywood clássico (Laura, Ser ou Não Ser, Adivinhe Quem Vem
 * Para Jantar, Quem Tem Medo de Virginia Woolf?, Gata em Teto de Zinco
 * Quente, Cassino Royale 1967), cinema de guerra (Uma Ponte Longe Demais,
 * Os Canhões de Navarone, A Batalha de Argel), Latino-americano (Nove
 * Rainhas, Sem Identidade/Sin Nombre), brasileiro recente (Ainda Estou
 * Aqui, vencedor do Oscar de Melhor Filme Internacional 2025), biopics
 * (Bob Marley: One Love, La Bamba, Lovelace), franquias de terror
 * (O Exorcista: O Início, O Exorcista III, Diário dos Mortos, Uma Chamada
 * Perdida) e diversos outros dramas, comédias e thrillers cult.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_19.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_19: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
