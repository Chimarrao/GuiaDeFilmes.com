<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

return new class extends Migration
{
    /**
     * Espaço mínimo livre em disco a manter, em bytes (segurança contra lotar o disco).
     */
    private const MIN_FREE_DISK_BYTES = 20 * 1024 * 1024 * 1024; // 20GB

    /**
     * Só roda em produção: faz download real de vídeo (fonte alternativa ao YouTube)
     * pra cada filme do banco (incluindo os recém-importados via CSV/n8n) que ainda
     * não tem nenhum trailer — nem YouTube (trailer_url) nem alternativo
     * (imdb_trailer_url). Sem limite de tamanho de arquivo (baixa o que vier).
     *
     * Seguro rodar de novo se parar no meio: só processa quem continua sem
     * nenhum dos dois campos preenchidos.
     */
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $movies = DB::table('movies')
            ->whereNotNull('external_ids')
            ->whereNull('imdb_trailer_url')
            ->where(function ($query) {
                $query->whereNull('trailer_url')->orWhere('trailer_url', '');
            })
            ->select('id', 'external_ids', 'title')
            ->get();

        $total = $movies->count();
        echo "  Total de filmes sem nenhum trailer (YouTube ou alternativo): {$total}\n";

        $destinationDir = public_path('trailers');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $done = 0;
        $notFound = 0;
        $skipped = 0;
        $maxAttempts = 5;

        foreach ($movies as $movie) {
            if (disk_free_space(public_path()) <= self::MIN_FREE_DISK_BYTES) {
                echo "  Espaço em disco abaixo do mínimo de segurança, parando (rode de novo depois pra continuar).\n";
                break;
            }

            $externalIds = json_decode($movie->external_ids, true);
            $imdbId = $externalIds['imdb_id'] ?? null;

            if (!$imdbId) {
                $skipped++;
                continue;
            }

            $url = "https://imdb.iamidiotareyoutoo.com/media/{$imdbId}";
            $cleanTitle = preg_replace('/[^a-zA-Z0-9.-]/', '', preg_replace('/\s+/', '-', $movie->title));
            $cleanTitle = substr($cleanTitle, 0, 50);
            $fileName = "{$imdbId}-{$cleanTitle}.mp4";
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

            $success = false;
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withoutVerifying()->timeout(120)->sink($destinationPath)->get($url);

                    if ($response->successful() && file_exists($destinationPath) && filesize($destinationPath) > 0) {
                        $success = true;
                        break;
                    }

                    $lastError = "HTTP {$response->status()}";
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                }

                echo "  [tentativa {$attempt}/{$maxAttempts} falhou] {$movie->title}: {$lastError}\n";
                if ($attempt < $maxAttempts) {
                    sleep(2);
                }
            }

            if ($success) {
                $localUrl = rtrim(config('app.url'), '/') . '/trailers/' . $fileName;
                DB::table('movies')->where('id', $movie->id)->update(['imdb_trailer_url' => $localUrl]);
                $done++;
                echo "  [OK] {$movie->title}\n";
            } else {
                // Sem trailer alternativo disponível nessa fonte: campo já estava
                // NULL, não há nada pra zerar — só loga e segue.
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
                $notFound++;
                echo "  [SEM TRAILER ALTERNATIVO] {$movie->title}: {$lastError}\n";
            }

            sleep(1);
        }

        echo "  Concluído: {$done} baixados, {$notFound} sem trailer alternativo disponível, {$skipped} sem IMDB ID.\n";
    }

    /**
     * Não reversível: os arquivos baixados não são apagados.
     */
    public function down(): void
    {
    }
};
