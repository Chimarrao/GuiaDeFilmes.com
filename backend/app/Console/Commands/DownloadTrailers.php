<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadTrailers extends Command
{
    /**
     * Assinatura do comando
     *
     * @var string
     */
    protected $signature = 'trailers:download {--ids= : IDs IMDB específicos separados por vírgula} {--limit= : Limite máximo de filmes para processar}';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Baixa trailers do IMDB e salva no disco local do servidor (public/trailers)';

    /**
     * Espaço mínimo livre em disco a manter, em bytes (segurança contra lotar o disco).
     *
     * @var int
     */
    private const MIN_FREE_DISK_BYTES = 20 * 1024 * 1024 * 1024; // 20GB

    /**
     * Contador total de filmes processados
     *
     * @var int
     */
    private int $totalProcessed = 0;

    /**
     * Contador de sucessos
     *
     * @var int
     */
    private int $totalSuccess = 0;

    /**
     * Contador de falhas
     *
     * @var int
     */
    private int $totalFailed = 0;

    /**
     * Contador de filmes ignorados
     *
     * @var int
     */
    private int $totalSkipped = 0;

    /**
     * Executa o comando de download de trailers
     *
     * Processa filmes do banco de dados ou IDs específicos fornecidos via parâmetro,
     * baixa trailers, comprime se necessário e salva no disco local do servidor.
     *
     * @return int Código de saída do comando
     */
    public function handle()
    {
        // Aumentar limite de memória para arquivos grandes (mais conservador)
        ini_set('memory_limit', '4096M');

        $this->info('🎬 Iniciando download de trailers...');
        $this->newLine();

        // Buscar filmes elegíveis
        $movies = $this->getEligibleMovies();

        // Se IDs específicos foram fornecidos, processá-los
        $specificIds = $this->option('ids');
        if ($specificIds) {
            /////
        }

        if ($movies->isEmpty()) {
            $this->warn('⚠️  Nenhum filme encontrado para processar.');
            $this->info('ℹ️  Use --ids=tt1234567,tt7654321 para especificar IDs IMDB ou --limit=N para limitar quantidade');
            return Command::SUCCESS;
        }

        $total = $movies->count();
        $this->info("📊 Total de filmes para processar: {$total}");
        $this->newLine();

        // Criar progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('Iniciando...');

        // Processar cada filme
        foreach ($movies as $movie) {
            if (!$this->hasEnoughDiskSpace()) {
                $this->newLine();
                $this->error('🛑 Espaço em disco abaixo do mínimo de segurança (' . $this->formatBytes(self::MIN_FREE_DISK_BYTES) . ' livres). Parando.');
                Log::warning('trailers:download interrompido por falta de espaço em disco.');
                break;
            }

            $this->totalProcessed++;

            $bar->setMessage("Processando: {$movie->title}");
            $bar->advance();

            try {
                $this->processMovie($movie);
            } catch (\Exception $e) {
                $this->totalFailed++;
                Log::error("Erro ao processar filme {$movie->title}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Relatório final
        $this->displayFinalReport();

        return Command::SUCCESS;
    }

    /**
     * Verifica se ainda há espaço em disco suficiente pra continuar baixando trailers.
     *
     * @return bool True se há espaço acima do mínimo de segurança
     */
    private function hasEnoughDiskSpace(): bool
    {
        $freeBytes = disk_free_space(public_path());

        return $freeBytes !== false && $freeBytes > self::MIN_FREE_DISK_BYTES;
    }

    /**
     * Busca filmes elegíveis para processamento
     *
     * Retorna uma coleção de filmes que ainda não possuem trailer baixado,
     * ordenados por popularidade. Suporta limite de quantidade e IDs específicos.
     *
     * @return \Illuminate\Database\Eloquent\Collection Coleção de filmes elegíveis
     */
    private function getEligibleMovies()
    {
        // IDs específicos passados como parâmetro
        $specificIds = $this->option('ids');
        $limit = $this->option('limit');

        if ($specificIds) {
            // Se IDs específicos foram fornecidos, ignorar limite
            $this->info("📋 Processando IDs específicos: {$specificIds}");
            return collect(); // Retornar vazio pois será tratado depois
        }

        // Buscar filmes sem trailer nenhum: sem trailer do YouTube (trailer_url) E sem trailer
        // baixado (imdb_trailer_url). Se já tem YouTube, não faz sentido baixar de novo.
        $query = Movie::whereNotNull('external_ids')
            ->whereNull('imdb_trailer_url')
            ->where(function ($query) {
                $query->whereNull('trailer_url')->orWhere('trailer_url', '');
            })
            ->orderBy('popularity', 'desc');

        // Aplicar limite se especificado
        if ($limit) {
            $query->limit((int)$limit);
            $this->info("📊 Aplicando limite de {$limit} filmes");
        }

        $movies = $query->inRandomOrder()->get();

        if ($movies->isEmpty()) {
            $this->warn('⚠️  Nenhum filme encontrado para processar.');
            if ($limit) {
                $this->info('💡 Tente remover o limite (--limit) ou especificar IDs com --ids');
            } else {
                $this->info('ℹ️  Use --ids=tt1234567,tt7654321 para especificar IDs IMDB ou --limit=N para limitar quantidade');
            }
        } else {
            $this->info("📊 Encontrados {$movies->count()} filmes para processar.");
        }

        return $movies;
    }

    /**
     * Processa um filme individual
     *
     * Executa todo o fluxo de processamento para um filme: download do trailer,
     * compressão se necessário, salvamento no disco local e atualização do banco de dados.
     *
     * @param \App\Models\Movie $movie Instância do modelo Movie a ser processado
     * @return void
     */
    private function processMovie($movie): void
    {
        $imdbId = $movie->external_ids['imdb_id'] ?? false;

        if (!$imdbId) {
            $this->totalSkipped++;
            $this->warn("  ⚠️  Filme {$movie->title} ignorado - sem IMDB ID");
            return;
        }

        $this->info("  🎬 Processando IMDB ID: {$imdbId} - {$movie->title}");

        // 1. Baixar vídeo do IMDB (fica em arquivo temporário)
        $videoData = $this->downloadVideo($imdbId);

        if (!$videoData) {
            $this->totalFailed++;
            $this->error("  ❌ Falha ao baixar vídeo para {$imdbId}");
            $movie->imdb_trailer_url = '';
            $movie->save();
            return;
        }

        $this->info("  📊 Vídeo baixado: " . $this->formatBytes($videoData['size']) . " ({$videoData['extension']})");

        // 2. Salvar no disco local (public/trailers), servido direto pelo Apache
        $localUrl = $this->saveToLocalDisk($videoData['tempFile'], $videoData['extension'], $imdbId, $movie->title);

        if (!$localUrl) {
            $this->totalFailed++;
            $this->error("  ❌ Falha ao salvar trailer no disco local");
            return;
        }

        $this->info("  ✅ Salvo em: {$localUrl}");

        // 3. Salvar URL no banco (apenas se for um filme real do banco)
        if (isset($movie->id) && $movie->id) {
            $movie->imdb_trailer_url = $localUrl;
            $movie->save();
            $this->info("  💾 URL salva no banco");
        } else {
            $this->info("  ℹ️  Modo teste - URL não salva no banco");
        }

        $this->totalSuccess++;
        Log::info("Trailer processado com sucesso para {$movie->title}: {$localUrl}");
    }

    /**
     * Baixa vídeo do trailer do IMDB
     *
     * Faz o download do trailer usando a API do IMDB, verifica o tamanho do arquivo,
     * comprime se necessário (para arquivos maiores que 20MB) e retorna os dados
     * (incluindo o caminho do arquivo temporário, ainda não apagado) prontos pra
     * serem movidos pro destino final.
     *
     * @param string $imdbId ID do filme no IMDB (formato ttXXXXXXX)
     * @return array|null Dados do vídeo (com 'tempFile') ou null se falhar
     */
    private function downloadVideo(string $imdbId): ?array
    {
        $tempFile = null;
        $success = false;

        try {
            $url = "https://imdb.iamidiotareyoutoo.com/media/{$imdbId}";

            // Criar arquivo temporário
            $tempFile = tempnam(sys_get_temp_dir(), 'trailer_');

            // Baixar arquivo
            $response = Http::withoutVerifying()->timeout(120)->sink($tempFile)->get($url);

            if (!$response->successful()) {
                Log::warning("Falha ao baixar trailer para IMDB ID {$imdbId}: HTTP {$response->status()}");
                return null;
            }

            // Verificar tamanho do arquivo (limite 20MB)
            $fileSize = filesize($tempFile);
            $maxSize = 20 * 1024 * 1024; // 20MB

            if ($fileSize > $maxSize) {
                $this->info("Arquivo maior que 20MB para IMDB ID {$imdbId}: " . $this->formatBytes($fileSize) . ", tentando comprimir...");
                $compressed = $this->compressVideo($tempFile);
                if (!$compressed) {
                    $this->warn("Falha ao comprimir vídeo para IMDB ID {$imdbId}");
                    return null;
                }
                // Verificar tamanho após compressão
                $fileSize = filesize($tempFile);
                if ($fileSize > $maxSize) {
                    $this->warn("Arquivo ainda maior que 20MB após compressão para IMDB ID {$imdbId}: " . $this->formatBytes($fileSize));
                    return null;
                }
                Log::info("Vídeo comprimido com sucesso para IMDB ID {$imdbId}: " . $this->formatBytes($fileSize));
            }

            // Verificar tamanho mínimo (5MB)
            $minSize = 5 * 1024 * 1024; // 5MB
            if ($fileSize < $minSize) {
                Log::warning("Arquivo muito pequeno para IMDB ID {$imdbId}: " . $this->formatBytes($fileSize) . " (mínimo: 5MB)");
                return null;
            }

            // Detectar tipo de conteúdo
            $contentType = $response->header('Content-Type') ?? 'video/mp4';
            $extension = $this->getExtensionFromContentType($contentType);

            $success = true;

            return [
                'tempFile' => $tempFile,
                'extension' => $extension,
                'contentType' => $contentType,
                'size' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao baixar trailer para IMDB ID {$imdbId}: {$e->getMessage()}");
            return null;
        } finally {
            // Só apaga aqui se não deu certo — em caso de sucesso, quem apaga é
            // saveToLocalDisk() depois de copiar o arquivo pro destino final.
            if (!$success && $tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Salva o vídeo baixado em public/trailers, servido como arquivo estático pelo Apache.
     *
     * @param string $tempFilePath Caminho do arquivo temporário já baixado
     * @param string $extension Extensão do arquivo (mp4, webm, etc.)
     * @param string $imdbId ID do filme no IMDB
     * @param string $movieTitle Título do filme para nome do arquivo
     * @return string|null URL pública do trailer ou null se falhar
     */
    private function saveToLocalDisk(string $tempFilePath, string $extension, string $imdbId, string $movieTitle): ?string
    {
        try {
            $cleanTitle = $this->sanitizeFileName($movieTitle);
            $fileName = "{$imdbId}-{$cleanTitle}.{$extension}";

            $destinationDir = public_path('trailers');
            if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
                Log::error("Não foi possível criar o diretório {$destinationDir}");
                return null;
            }

            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

            if (!copy($tempFilePath, $destinationPath)) {
                Log::error("Falha ao copiar trailer para {$destinationPath} ({$imdbId})");
                return null;
            }

            return rtrim(config('app.url'), '/') . '/trailers/' . $fileName;

        } catch (\Exception $e) {
            Log::error("Erro ao salvar trailer no disco local ({$imdbId}): {$e->getMessage()}");
            return null;
        } finally {
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    /**
     * Sanitiza o nome do arquivo para uso seguro
     *
     * Remove caracteres especiais e espaços, substituindo por hífens,
     * e limita o tamanho máximo do nome.
     *
     * @param string $name Nome original do arquivo
     * @return string Nome sanitizado
     */
    private function sanitizeFileName(string $name): string
    {
        // Remove espaços e caracteres especiais
        $name = preg_replace('/\s+/', '-', $name);
        $name = preg_replace('/[^a-zA-Z0-9.-]/', '', $name);

        // Limitar tamanho
        return substr($name, 0, 50);
    }

    /**
     * Obtém extensão do arquivo a partir do tipo de conteúdo
     *
     * Mapeia tipos MIME de vídeo para extensões de arquivo apropriadas.
     *
     * @param string $contentType Tipo MIME do conteúdo
     * @return string Extensão do arquivo (mp4 por padrão)
     */
    private function getExtensionFromContentType(string $contentType): string
    {
        $map = [
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
        ];

        foreach ($map as $mime => $ext) {
            if (stripos($contentType, $mime) !== false) {
                return $ext;
            }
        }

        return 'mp4'; // fallback
    }

    /**
     * Exibe o relatório final do processamento
     *
     * Mostra estatísticas completas do processamento incluindo sucessos,
     * falhas, ignorados e taxa de sucesso.
     *
     * @return void
     */
    private function displayFinalReport(): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RELATÓRIO FINAL - DOWNLOAD DE TRAILERS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->info("✅ Sucesso:     {$this->totalSuccess}");
        $this->info("❌ Falhas:      {$this->totalFailed}");
        $this->info("⏭️  Ignorados:   {$this->totalSkipped}");
        $this->info("📊 Total:       {$this->totalProcessed}");

        $this->newLine();

        if ($this->totalSuccess > 0) {
            $successRate = round(($this->totalSuccess / $this->totalProcessed) * 100, 2);
            $this->info("🎯 Taxa de sucesso: {$successRate}%");
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    /**
     * Comprime vídeo usando script Python
     *
     * Executa o script de compressão Python com otimizações específicas
     * para cada sistema operacional (Windows/Linux).
     *
     * @param string $filePath Caminho para o arquivo de vídeo a ser comprimido
     * @return bool True se a compressão foi bem-sucedida, false caso contrário
     */
    private function compressVideo(string $filePath): bool
    {
        try {
            $scriptPath = base_path('scripts/compress_video.py');

            if (!file_exists($scriptPath)) {
                Log::error("Script de compressão não encontrado: {$scriptPath}");
                return false;
            }

            // Criar arquivo temporário para output (evita erro de acesso negado no Windows)
            $tempOutput = tempnam(sys_get_temp_dir(), 'compressed_') . '.mp4';

            // Detectar sistema operacional e escolher Python apropriado
            $isWindows = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';

            if ($isWindows) {
                $python = 'python';
                // No Windows, usar UTF-8
                $command = "chcp 65001 > nul && python \"{$scriptPath}\" \"{$filePath}\" \"{$tempOutput}\" 2>&1";
            } else {
                $venvPython = base_path('venv/bin/python3');

                // Se o venv existir, usa ele — senão usa python3 global
                if (file_exists($venvPython)) {
                    $python = escapeshellcmd($venvPython);
                } else {
                    $python = 'python3';
                }
                // No Linux, reduzir prioridade e threads (FFmpeg limitado a 1 thread)
                $command = "nice -n 10 {$python} \"{$scriptPath}\" \"{$filePath}\" \"{$tempOutput}\" 2>&1";
            }

            Log::info("Executando comando de compressão: " . $command);

            // Executar script Python
            $output = [];
            $returnCode = 0;

            exec($command, $output, $returnCode);

            $outputStr = implode("\n", $output);

            if ($returnCode === 0 && file_exists($tempOutput)) {
                // Sobrescrever arquivo original com versão comprimida
                if (copy($tempOutput, $filePath)) {
                    Log::info("Compressão bem-sucedida: {$outputStr}");
                    // Limpar arquivo temporário
                    unlink($tempOutput);
                    return true;
                } else {
                    Log::error("Falha ao sobrescrever arquivo original após compressão");
                    unlink($tempOutput);
                    return false;
                }
            } else {
                Log::error("Falha na compressão: {$outputStr}");
                // Limpar arquivo temporário se existir
                if (file_exists($tempOutput)) {
                    unlink($tempOutput);
                }
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Erro ao executar compressão: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Formata bytes em formato legível
     *
     * Converte bytes em unidades apropriadas (B, KB, MB, GB, TB)
     * com duas casas decimais.
     *
     * @param int $bytes Número de bytes
     * @return string String formatada com unidade
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}