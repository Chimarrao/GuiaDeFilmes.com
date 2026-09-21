<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\JustWatchController;

return new class extends Migration
{
    /**
     * Quantos filmes com resultado vazio ("[]") reprocessar nessa leva única.
     * O cron diário (justwatch:backfill --empty) cuida do resto do backlog
     * aos poucos depois disso.
     */
    private const LIMIT = 15000;

    /**
     * Reprocessa filmes que ficaram com justwatch_watch_info = "[]" (67% da base
     * inteira, ~36.700 filmes, porque o cron diário só reprocessava quem estava
     * NULL). Prioriza os mais relevantes (popularidade), excluindo curtas
     * obscuros (runtime < 40min) — plataformas de streaming mudam de catálogo,
     * então um resultado vazio de meses atrás pode estar desatualizado.
     *
     * Pode demorar bastante (uma requisição HTTP por filme + delay) — roda em
     * background no servidor (ex: nohup php artisan migrate &).
     */
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $movies = DB::table('movies')
            ->whereNotNull('justwatch_watch_info')
            ->whereRaw('JSON_LENGTH(justwatch_watch_info) = 0')
            ->where('adult', 0)
            ->where(function ($query) {
                $query->whereNull('runtime')->orWhere('runtime', '>=', 40);
            })
            ->orderByDesc('popularity')
            ->limit(self::LIMIT)
            ->select('id', 'tmdb_id', 'title', 'release_date')
            ->get();

        $total = $movies->count();
        echo "  Total de filmes vazios a reprocessar (dos mais populares): {$total}\n";

        $updated = 0;
        $stillEmpty = 0;
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

                if ($count > 0) {
                    $updated++;
                    echo "  [ACHOU] {$movie->title} ({$count} ofertas)\n";
                } else {
                    $stillEmpty++;
                }
            } catch (\Throwable $e) {
                $failed++;
                echo "  [ERRO] {$movie->title}: {$e->getMessage()}\n";
            }

            sleep(1);
        }

        echo "  Concluído: {$updated} agora com plataforma, {$stillEmpty} continuam vazios, {$failed} com erro.\n";
    }

    public function down(): void
    {
    }
};
