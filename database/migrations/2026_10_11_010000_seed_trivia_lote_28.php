<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Lote 28 de curiosidades (trivia): 19 filmes.
 *
 * Rodada final de verificação de esgotamento, pedida explicitamente depois
 * do lote 27 (que já tinha retornos decrescentes: 26 -> 27 filmes). A
 * navegação pura por `tmdb_vote_count` já estava esgotada (páginas 300+ sem
 * reconhecimento). Esta rodada tentou 8 frentes temáticas específicas ainda
 * não exploradas nos lotes anteriores, todas via `GET
 * /api/movies/search?q=<título>` com conferência de tmdb_id, sinopse,
 * gênero, país e duração antes de incluir:
 *
 * 1. Ganhadores de festivais B/prêmios de carreira associados a Guillermo
 *    del Toro ("Cronos", "A Espinha do Diabo") e um ganhador de Goya
 *    ("O Orfanato", produzido por del Toro).
 * 2. Cinema queer/LGBTQ+: "120 Batimentos Por Minuto" (Cannes 2017 + Queer
 *    Palm), "Tangerina" (filmado em iPhone), "A Criada" (adaptação de
 *    Sarah Waters, Competição de Cannes 2016).
 * 3. Biografias musicais e documentários/filmes de concerto de rock:
 *    "Gimme Shelter" (Rolling Stones/Altamont), "Monterey Pop"
 *    (Hendrix/Redding), "HOMECOMING" (Beyoncé no Coachella),
 *    "Procurando Sugar Man" (Oscar 2013), "Straight Outta Compton" (N.W.A.).
 * 4. Horror internacional: "Kairo"/Pulse (J-horror, Un Certain Regard
 *    Cannes 2001).
 * 5. Spaghetti western fora de Leone/Corbucci: "Keoma" (Franco Nero,
 *    Enzo G. Castellari).
 * 6. (Concert films cobertos no item 3 acima.)
 * 7. Adaptações de HQ cult fora do mainstream Marvel/DC: "Ghost World"
 *    (Daniel Clowes) e "Anti-Herói Americano"/American Splendor
 *    (Harvey Pekar, Sundance 2003).
 * 8. Curtas de Oscar: nenhum candidato de confiança encontrado no catálogo
 *    (curtas-metragens raramente estão catalogados como filme completo) —
 *    frente sem resultado.
 *
 * Fora das 8 frentes pedidas, dois dramas de festival/premiação adjacentes
 * também foram encontrados com confiança: "Beasts of No Nation" (primeiro
 * original Netflix com lançamento simultâneo em cinema, boicotado por
 * grandes redes) e "30 Dias de Noite" (quadrinhos de Steve Niles, rodado na
 * Nova Zelândia) e "Judas e o Messias Negro" (Oscar de Kaluuya e de canção
 * original).
 *
 * Todos os 19 tmdb_ids foram copiados diretamente do campo `tmdb_id`
 * retornado por `GET /api/movies/search` — nenhum id foi digitado de
 * memória — e sinopse/gênero/país/duração de cada um foi lido e cruzado
 * com o filme pretendido antes da inclusão.
 *
 * Conclusão desta rodada: tamanho (19) ficou dentro da faixa baixa
 * esperada (~15-20), confirmando esgotamento real da fonte de alta
 * confiança. Muitas buscas por título "óbvio" das 8 frentes (ex.:
 * Whiplash, Moonlight, Hereditário, Corra!, Brokeback Mountain, Rocketman,
 * Scott Pilgrim, Oldboy, Kick-Ass, Persépolis, A Garota Dinamarquesa,
 * Retrato de uma Jovem em Chamas) já estavam cobertas em lotes anteriores
 * — sinal de que os títulos de maior reconhecimento dessas categorias já
 * foram capturados organicamente pela navegação por vote_count nos lotes
 * 1-27. Ver .claude/skills/curiosidades-filmes/SKILL.md para o processo
 * completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_28.json');

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

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_28: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
