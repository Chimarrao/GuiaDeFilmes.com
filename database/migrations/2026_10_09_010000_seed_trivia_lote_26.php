<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 26 de curiosidades (trivia): 26 filmes.
 *
 * Seguindo a estratégia direcionada do lote 25 (busca por título via
 * `GET /api/movies/search?q=<título>` em vez de navegar vote_count às
 * ciegas), este lote explorou filmografias de diretores de autor ainda
 * não cobertas nos lotes 1-25, mais clássicos de terror americano/inglês
 * dos anos 1930-1950:
 *
 * 1. Diretores europeus de autor: Ingmar Bergman ("Fanny e Alexander"),
 *    Lars von Trier ("Os Idiotas"), Thomas Vinterberg ("A Comunidade"),
 *    Béla Tarr ("O Tango de Satã"), Lukas Moodysson ("Amigas de
 *    Colégio"/Fucking Åmål), Luchino Visconti ("Noites Brancas"), Jacques
 *    Demy ("Os Guarda-Chuvas do Amor", "Pele de Asno"), Alain Resnais
 *    ("Hiroshima, Meu Amor"), Louis Malle ("Lacombe Lucien"), Georges
 *    Franju ("Os Olhos Sem Rosto"), Max Ophüls ("Desejos Proibidos"),
 *    Federico Fellini ("Julieta dos Espíritos").
 * 2. Cinema asiático de autor: Hou Hsiao-hsien ("Flores de Xangai",
 *    Taiwan), Kenji Mizoguchi ("Intendente Sansho", Japão).
 * 3. Cinema latino-americano: Glauber Rocha ("Terra em Transe", Cinema
 *    Novo brasileiro), Alejandro Jodorowsky ("Santa Sangre", "A Montanha
 *    Sagrada", produções México/Itália/EUA).
 * 4. Clássicos de terror em série: ciclo Universal/Hammer de Frankenstein
 *    ("A Noiva de Frankenstein" 1935, "Frankenstein Encontra O
 *    Lobisomem" 1943, "A Maldição de Frankenstein" 1957), e filmes de
 *    museu de cera ("O Gabinete das Figuras de Cera" 1924, "Museu de
 *    Cera" 1953), além de "A Marca do Vampiro" (1935, Tod Browning).
 * 5. Elia Kazan/John Steinbeck: "Viva Zapata" (roteiro do futuro Nobel de
 *    Literatura John Steinbeck).
 * 6. Cinema finlandês recente: "Vagão nº 6" (2021), vencedor do Grande
 *    Prêmio do Júri em Cannes.
 *
 * Todos os 26 tmdb_ids foram copiados diretamente do campo `tmdb_id`
 * retornado por `GET /api/movies/search?q=<título>` — nenhum id foi
 * digitado de memória — e a sinopse/gênero/país/duração de cada um foi
 * lido e cruzado com o filme pretendido antes da inclusão (ver
 * verificação completa com runtime e overview de cada um feita antes de
 * escrever as curiosidades).
 *
 * Buscas que não deram resultado confiável e foram descartadas deste
 * lote: adaptações de Nobel de Literatura como García Márquez ("O Amor
 * nos Tempos do Cólera", "Crônica de uma Morte Anunciada") e "A Casa dos
 * Espíritos" (Isabel Allende) não retornaram correspondência no catálogo;
 * "Kieślowski: Três Cores" (Azul/Branco/Vermelho) e "Charulata"/"Devi"
 * (Satyajit Ray) também não foram encontrados. "Deus e o Diabo na Terra
 * do Sol", "Vidas Secas" e "Macunaíma" (Cinema Novo) só retornaram
 * material de extras/documentário, não os filmes originais — ficaram de
 * fora.
 *
 * Nenhuma divergência título/sinopse foi encontrada nos 26 filmes que
 * entraram neste lote.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_26.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_26: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
