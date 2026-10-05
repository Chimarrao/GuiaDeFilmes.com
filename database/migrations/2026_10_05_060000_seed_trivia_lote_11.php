<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 11 de curiosidades (trivia): 44 filmes.
 *
 * Mesma fonte de candidatos dos lotes 8-10: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), excluindo
 * todos os tmdb_ids já presentes em qualquer database/data/movie_trivia*.json
 * anterior. Cada tmdb_id foi copiado diretamente do campo `tmdb_id`
 * retornado por essa API — nenhum id foi digitado de memória — e o `title`
 * retornado foi conferido contra o filme pretendido antes de escrever
 * qualquer fato.
 *
 * Cobertura: mais 6 filmes de James Bond da era Connery/Lazenby/Moore/Dalton
 * (A Serviço Secreto de Sua Majestade, Os Diamantes São Eternos, O Homem com
 * a Pistola de Ouro, Octopussy, Marcado para a Morte, Somente para Seus
 * Olhos — fechando praticamente toda a franquia clássica), Studio Ghibli
 * além de Miyazaki (O Conto da Princesa Kaguya e Da Colina Kokuriko, de
 * Takahata e Goro Miyazaki, e As Memórias de Marnie), cinema coreano de
 * Park Chan-wook (Oldboy, Lady Vingança), Almodóvar (Volver, Tudo Sobre
 * Minha Mãe), Kurosawa (Ran), Spike Lee (Faça a Coisa Certa, Malcolm X),
 * Cronenberg (eXistenZ, Um Método Perigoso), Coppola (A Conversação),
 * clássicos de noir e Hollywood dourada (O Terceiro Homem, O Falcão
 * Maltês), vencedores recentes de Cannes/Oscar internacionais (Emilia
 * Pérez, Cafarnaum, Dias Perfeitos, Vidas Passadas) e dramas premiados
 * (As Horas, Ray, Em Nome do Pai, Dúvida, Cartas de Iwo Jima / A Conquista
 * da Honra — o par de filmes-espelho de Clint Eastwood sobre Iwo Jima).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_11.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_11: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
