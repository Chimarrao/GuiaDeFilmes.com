<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 25 de curiosidades (trivia): 52 filmes.
 *
 * Diferente dos lotes anteriores (18-24), que levantaram candidatos
 * navegando `GET /api/movies/filter?sortBy=vote_count` às ciegas, este
 * lote priorizou buscas DIRECIONADAS via
 * `GET /api/movies/search?q=<título>`, conforme sugerido depois que a
 * navegação por vote_count passou a produzir cada vez mais filmes obscuros
 * sem dado confiável na memória. Fontes de candidatos:
 *
 * 1. Filmografias específicas de diretores de cinema de autor/arthouse que
 *    eu conheço a fundo: Satyajit Ray (Trilogia de Apu), Yasujiro Ozu,
 *    Wong Kar-wai, Hayao Miyazaki, Jean-Luc Godard, François Truffaut,
 *    Luis Buñuel, Akira Kurosawa, Rainer Werner Fassbinder, Werner Herzog,
 *    Aki Kaurismäki, Jiří Menzel, Andrzej Wajda, Paweł Pawlikowski, Asghar
 *    Farhadi, Manoel de Oliveira, Pedro Almodóvar, Robert Bresson, Jacques
 *    Tati, Éric Rohmer, Marcel Pagnol, Satoshi Kon, Theo Angelopoulos,
 *    Agnès Varda, Alan Parker, Jean-Pierre Jeunet, Shohei Imamura,
 *    Federico Fellini, Pier Paolo Pasolini, Jerzy Kawalerowicz e Nuri
 *    Bilge Ceylan.
 * 2. Escritores vencedores do Nobel de Literatura adaptados ao cinema:
 *    Kazuo Ishiguro ("Vestígios do Dia"), Ernest Hemingway ("O Velho e o
 *    Mar", "Por Quem os Sinos Dobram"), Isaac Bashevis Singer ("Yentl"),
 *    Rudyard Kipling ("Gunga Din").
 * 3. TV-para-cinema e animação/design: "Veronica Mars" (filme financiado
 *    por Kickstarter após o cancelamento da série), "Klaus" (indicado ao
 *    Oscar de animação), "Rams" (documentário sobre o designer Dieter
 *    Rams — cuidado: não é o drama islandês de ovelhas do mesmo nome).
 *
 * Todos os 52 tmdb_ids foram copiados diretamente do campo `tmdb_id`
 * retornado por `GET /api/movies/search?q=<título>` — nenhum id foi
 * digitado de memória — e a sinopse de cada um foi lida e cruzada com o
 * filme pretendido antes da inclusão. Nenhum dos 52 estava em qualquer
 * `database/data/movie_trivia*.json` anterior (lotes 1-24).
 *
 * Alerta de possível confusão evitada: a busca por "Rams" trouxe só um
 * resultado no catálogo (tmdb_id 510243), um documentário americano de
 * 2018 sobre o designer industrial Dieter Rams (dirigido por Gary
 * Hustwit) — não o premiado drama islandês "Hrútar" (2015, também
 * conhecido internacionalmente como "Rams"), que não está no catálogo.
 * As curiosidades escritas são sobre o documentário de design, confirmado
 * pela sinopse/gênero/país de produção retornados pela API.
 *
 * Nenhuma divergência título/sinopse tipo Rio Bravo/Rio Grande (lote 23)
 * foi encontrada nos 52 filmes que entraram neste lote.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_25.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_25: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
