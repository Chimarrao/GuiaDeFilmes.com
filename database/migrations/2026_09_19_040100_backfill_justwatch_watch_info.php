<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\JustWatchController;

return new class extends Migration
{
    /**
     * Só roda em produção: chama a API externa do JustWatch (via JustWatchController,
     * o mesmo usado pelo comando justwatch:backfill) filme a filme, pra preencher
     * justwatch_watch_info onde está NULL — incluindo os filmes recém-importados.
     *
     * Seguro rodar de novo se parar no meio: só processa quem continua NULL.
     */
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $movies = DB::table('movies')
            ->select('id', 'tmdb_id', 'title', 'release_date')
            ->whereNull('justwatch_watch_info')
            ->whereNotNull('tmdb_id')
            ->orderBy('id', 'asc')
            ->get();

        $total = $movies->count();
        echo "  Total de filmes sem dado de JustWatch: {$total}\n";

        $done = 0;
        $failed = 0;

        foreach ($movies as $movie) {
            $title = trim($movie->title ?? '');

            if ($title === '') {
                continue;
            }

            try {
                $request = new Request([
                    'query' => $title,
                    'release_date' => $movie->release_date,
                ]);

                $controller = new JustWatchController();
                $response = $controller->search($request);
                $data = json_decode($response->getContent(), true);

                if (isset($data['error'])) {
                    $failed++;
                    echo "  [ERRO] {$movie->title}: {$data['error']}\n";
                    sleep(1);
                    continue;
                }

                $offers = $data['offers'] ?? $data;

                DB::table('movies')
                    ->where('id', $movie->id)
                    ->update(['justwatch_watch_info' => json_encode($offers, JSON_UNESCAPED_UNICODE)]);

                $count = is_array($offers) ? count($offers) : 1;
                $done++;
                echo "  [OK] {$movie->title} ({$count} ofertas)\n";
            } catch (\Throwable $e) {
                $failed++;
                echo "  [ERRO] {$movie->title}: {$e->getMessage()}\n";
            }

            sleep(1);
        }

        echo "  Concluído: {$done} preenchidos, {$failed} com erro.\n";
    }

    /**
     * Não reversível: não faz sentido "desfazer" os dados de JustWatch preenchidos.
     */
    public function down(): void
    {
    }
};
