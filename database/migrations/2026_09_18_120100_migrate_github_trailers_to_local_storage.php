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
     * Baixa, um a um, os trailers ainda hospedados no GitHub e salva em
     * public/trailers (disco local do servidor). Pode demorar bastante
     * (uma requisição HTTP por filme, com um pequeno delay entre elas) —
     * rode em background no servidor (ex: nohup php artisan migrate &).
     *
     * É seguro rodar de novo se parar no meio (via migrate:rollback + migrate):
     * só processa o que ainda aponta pro GitHub.
     */
    public function up(): void
    {
        $movies = DB::table('movies')
            ->whereNotNull('imdb_trailer_url')
            ->where('imdb_trailer_url', 'like', '%github%')
            ->select('id', 'imdb_trailer_url', 'title')
            ->get();

        $total = $movies->count();
        echo "  Total de trailers a migrar do GitHub para o disco local: {$total}\n";

        $destinationDir = public_path('trailers');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $done = 0;
        $failed = 0;
        $maxAttempts = 5;

        foreach ($movies as $movie) {
            if (disk_free_space(public_path()) <= self::MIN_FREE_DISK_BYTES) {
                echo "  Espaço em disco abaixo do mínimo de segurança, parando (rode de novo depois pra continuar).\n";
                break;
            }

            $fileName = basename(parse_url($movie->imdb_trailer_url, PHP_URL_PATH));
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

            $success = false;
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withoutVerifying()->timeout(120)->sink($destinationPath)->get($movie->imdb_trailer_url);

                    if ($response->successful()) {
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
                // Esgotou as tentativas: zera o link em vez de deixar apontando pro GitHub morto
                DB::table('movies')->where('id', $movie->id)->update(['imdb_trailer_url' => null]);
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
                $failed++;
                echo "  [FALHOU após {$maxAttempts} tentativas, link zerado] {$movie->title}: {$lastError}\n";
            }

            sleep(1);
        }

        echo "  Concluído: {$done} migrados, {$failed} zerados por falha.\n";
    }

    /**
     * Não reversível: os arquivos baixados não são apagados nem as URLs do
     * GitHub restauradas (o nome do arquivo não garante reconstrução da URL original).
     */
    public function down(): void
    {
        // Intencionalmente vazio.
    }
};
