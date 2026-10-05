<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 24 de curiosidades (trivia): 40 filmes.
 *
 * Fonte de candidatos: GET
 * https://guiadefilmes.com/api/movies/filter?sortBy=vote_count&limit=50&page=N
 * (catálogo real em produção, ordenado por tmdb_vote_count desc), páginas
 * 181-220 (faixa ~240-268 de tmdb_vote_count), continuação do lote 23.
 * Excluindo todos os tmdb_ids já presentes em qualquer
 * database/data/movie_trivia*.json anterior. Cada tmdb_id foi copiado
 * diretamente do campo `tmdb_id` retornado pela API — nenhum id foi
 * digitado de memória.
 *
 * Nessa faixa de vote_count a fração de filmes obscuros/sem dados
 * confiáveis na memória subiu bastante (como esperado); de ~2000
 * candidatos novos levantados nas 40 páginas, só 40 passaram o teste de
 * confiança + especificidade (2 fatos checáveis, não-genéricos cada).
 *
 * Três candidatos descartados por risco de dado/fato não verificável
 * (não entraram neste lote):
 * - tmdb_id 86328 ("Terrifier" no site, release_date 2011): sinopse vazia
 *   e é provavelmente o curta-metragem original de Damien Leone (2011),
 *   distinto do longa "Terrifier" (2016/2017) que já tem outro tmdb_id
 *   próprio no catálogo (Terrifier 2, 3, "O Início" etc. aparecem
 *   corretamente separados) — risco real de confundir fatos do curta com
 *   os do longa famoso, então pulado.
 * - tmdb_id 1612018 ("Jackass: O Último é o Melhor" no site): release_date
 *   retornada é 2026-06-25 (futuro) e a sinopse não corresponde ao
 *   "Jackass Forever" (2022, que manteve esse nome em inglês no Brasil) —
 *   parece ser um filme ainda não lançado/distinto. Pulado por não dar
 *   pra escrever curiosidade de bastidor verificável de um filme sem
 *   lançamento confirmado.
 * - tmdb_id 820232 ("Demon Slayer: Kimetsu no Yaiba - Vínculo de irmãos"):
 *   release_date salva (2019-03-29) não bate com nenhuma data que eu
 *   conseguisse confirmar com certeza para o especial/compilação
 *   correspondente — pulado por falta de confiança na data exata.
 *
 * Nenhuma divergência título/sinopse (tipo Rio Bravo/Rio Grande do lote
 * 23) foi encontrada nos 40 filmes que entraram neste lote.
 *
 * Cobertura variada: clássicos de Hollywood e cinema mudo/pré-Code (O
 * Cantor de Jazz, Alta Sociedade, A Marca do Zorro, Sansão e Dalila, Júlio
 * César, O Rei dos Reis), cinema europeu de autor (Milagre em Milão,
 * Borsalino, Sorrisos de uma Noite de Amor/Bergman, Cría Cuervos/Saura, A
 * Árvore dos Tamancos/Olmi, A Caixa de Pandora/Pabst, Até o Fim do
 * Mundo/Wenders, Tillsammans/Moodysson, Alexander Nevsky/Eisenstein),
 * documentários premiados (Quando Éramos Reis, Nascidos em Bordéis, Fed
 * Up, STILL: Ainda Sou Michael J. Fox), dramas de gângster (Gangster No.
 * 1, Billy Bathgate, F.I.S.T.), cinema estrangeiro premiado (Moscou Não
 * Acredita em Lágrimas, Que Fiz Eu Para Merecer Isto?, Revanche, Corsage,
 * You Are the Apple of My Eye) e animação/pop recente (South Park:
 * Entrando no Panderverso, Attack on Titan: O Último Ataque, Blue Lock O
 * Filme, Batman Ninja vs. Liga da Yakuza, Psycho-Pass: O Filme, dois
 * especiais de Doctor Who, Velozes e Furiosos: Turbo-Charged Prelude).
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_24.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_24: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
