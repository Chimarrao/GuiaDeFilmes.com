<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 9 de curiosidades (trivia): 57 filmes.
 *
 * Mesma fonte de candidatos do lote 8: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior (incluindo o lote 8). Cada tmdb_id foi copiado diretamente do
 * campo `tmdb_id` retornado por essa API — nenhum id foi digitado de
 * memória — e o `title` retornado foi conferido contra o filme pretendido
 * antes de escrever qualquer fato.
 *
 * Cobertura: 5 filmes de James Bond ainda não cobertos (GoldenEye, O Amanhã
 * Nunca Morre, O Mundo Não é o Bastante, Um Novo Dia para Morrer, Moscou
 * Contra 007), filmografia de Bong Joon-ho (Memórias de um Assassino, O
 * Hospedeiro, Okja), Coen Brothers (Ave César!, A Balada de Buster
 * Scruggs), Terrence Malick (Além da Linha Vermelha, A Árvore da Vida),
 * Woody Allen (Match Point, Vicky Cristina Barcelona), cinema internacional
 * (Incêndios - Canadá/Villeneuve, A Grande Beleza - Itália, Amor à Flor da
 * Pele - Hong Kong/Wong Kar-wai, Retrato de uma Jovem em Chamas - França,
 * Triângulo da Tristeza - Suécia, Relatos Selvagens - Argentina), clássicos
 * de comédia (Apertem os Cintos o Piloto Sumiu, Um Tira da Pesada, O Jovem
 * Frankenstein, Austin Powers) e dramas premiados (O Leitor, Os Sonhadores,
 * Silêncio, Dois Papas, Ataque dos Cães).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_9.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_9: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
