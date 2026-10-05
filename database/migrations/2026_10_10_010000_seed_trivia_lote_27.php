<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 27 de curiosidades (trivia): 27 filmes.
 *
 * Esta rodada foi especificamente uma "verificação de esgotamento real" da
 * fonte de candidatos, feita em duas frentes:
 *
 * 1. Reconfirmação da navegação por `tmdb_vote_count` em páginas bem mais
 *    altas que o normal (250, 300, 350 e 400 de `GET
 *    /api/movies/filter?sortBy=vote_count&limit=50&page=N`, cobrindo
 *    vote_count de ~112 até ~26). A quase totalidade dos títulos ali é
 *    genuinamente obscura (TV movies estrangeiras sem tradução, curtas de
 *    animação antigos, straight-to-video dos anos 1980-90) — sem
 *    reconhecimento nem confiança suficiente pra escrever 2 fatos
 *    específicos. Só uma fração pequena da página 250 (vote_count ~111-112)
 *    rendeu candidatos aproveitáveis.
 * 2. Busca direcionada por título via `GET /api/movies/search?q=<título>`
 *    pra temas sugeridos: outros ganhadores de Palma de Ouro/Urso de
 *    Ouro/Leão de Ouro ainda não cobertos (Ken Loach - "Eu, Daniel Blake";
 *    Julia Ducournau - "Titane"; Jacques Audiard - "Dheepan: O Refúgio";
 *    Jafar Panahi - "Taxi Teerã"; Kim Ki-duk - "Pieta"), mais diretores
 *    asiáticos (Isao Takahata/Hayao Miyazaki - "Horus, Príncipe do Sol";
 *    Takeshi Kitano - "Aquiles e a Tartaruga"; Ishirō Honda - "Matango"),
 *    cinema do Leste Europeu (Béla Tarr - "As Harmonias de Werckmeister"),
 *    documentários de natureza/ciência (trilogia Qatsi de Godfrey Reggio -
 *    "Baraka" e "Powaqqatsi"; "Blackfish"; "Dig!"; "Concert for George"),
 *    e séries B de ficção científica/monstros japoneses dos anos 1950-60
 *    ("Tarântula!" 1955, "Ghidorah: O Monstro Tricéfalo" 1964, "O
 *    Despertar dos Monstros" 1968). Completam o lote clássicos de cinema
 *    mudo/pré-Code (Chaplin - "Casa de Penhores"; von Stroheim - "Esposas
 *    Ingênuas"; Howard Hughes - "Anjos do Inferno"), cinema italiano
 *    (Mastroianni/Pasolini - "O Belo Antônio"; Benigni - "Berlinguer ti
 *    voglio bene"), TV movies de franquia (Bill Bixby - "A Morte do
 *    Incrível Hulk"; "Mansfield Park" 2007 da ITV) e "The Program" (1993,
 *    famoso pela cena cortada depois de mortes reais por imitação) e "Le
 *    Grand Bleu"/"Imensidão Azul" (1988, baseado em mergulhadores reais) e
 *    "A Estirpe dos Malditos" (Children of the Damned, 1964).
 *
 * Todos os 27 tmdb_ids foram copiados diretamente do campo `tmdb_id`
 * retornado por `GET /api/movies/search` ou `/api/movies/filter` — nenhum
 * id foi digitado de memória — e sinopse/gênero/país/duração de cada um
 * foi lido e cruzado com o filme pretendido antes da inclusão.
 *
 * Conclusão desta rodada: a navegação por vote_count está, de fato, perto
 * do limite de confiança (páginas 250-400 renderam só uma fração pequena
 * de candidatos aproveitáveis entre dezenas de títulos obscuros demais pra
 * ter certeza). A busca direcionada por tema/prêmio/diretor ainda rendeu um
 * lote de tamanho razoável (27), mas bem menor que os lotes "fáceis" do
 * início do projeto — sinal de que o catálogo de alta confiança está
 * ficando mais raro e os próximos lotes devem continuar menores.
 *
 * Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_27.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_27: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
