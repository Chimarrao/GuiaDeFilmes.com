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
    protected $description = 'Baixa trailers do IMDB e faz upload para GitHub CDN';

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
     * baixa trailers, comprime se necessário e faz upload para GitHub.
     *
     * @return int Código de saída do comando
     */
    public function handle()
    {
        // Aumentar limite de memória para arquivos grandes (mais conservador)
        ini_set('memory_limit', '4096M');

        $this->info('🎬 Iniciando download de trailers...');
        $this->newLine();

        // Validar configurações
        if (!$this->validateConfig()) {
            return Command::FAILURE;
        }

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
     * Valida as configurações necessárias para o comando
     *
     * Verifica se todas as variáveis de ambiente necessárias estão configuradas
     * no arquivo .env para o funcionamento correto do comando.
     *
     * @return bool True se todas as configurações estão válidas, false caso contrário
     */
    private function validateConfig(): bool
    {
        $required = [
            'GITHUB_USER' => env('GITHUB_USER'),
            'GITHUB_REPO' => env('GITHUB_REPO'),
            'GITHUB_TOKEN' => env('GITHUB_TOKEN'),
            'GITHUB_VIDEO_FOLDER' => env('GITHUB_VIDEO_FOLDER'),
        ];

        $missing = [];
        foreach ($required as $key => $value) {
            if (empty($value)) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $this->error('❌ Configurações ausentes no .env:');
            foreach ($missing as $key) {
                $this->error("  • {$key}");
            }
            return false;
        }

        $this->info('✅ Configurações validadas');
        return true;
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

        // Buscar filmes reais sem trailer
        $query = Movie::whereNotNull('external_ids')
            ->where(function ($query) {
                $query->whereNull('imdb_trailer_url');
            })
            ->orderBy('popularity', 'desc');

        // Aplicar limite se especificado
        if ($limit) {
            $query->limit((int)$limit);
            $this->info("📊 Aplicando limite de {$limit} filmes");
        }

        $movies = $query->get();

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
     * compressão se necessário, upload para GitHub e atualização do banco de dados.
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

        // 1. Baixar vídeo do IMDB
        $videoData = $this->downloadVideo($imdbId);

        if (!$videoData) {
            $this->totalFailed++;
            $this->error("  ❌ Falha ao baixar vídeo para {$imdbId}");
            $movie->imdb_trailer_url = '';
            $movie->save();
            return;
        }

        $this->info("  📊 Vídeo baixado: " . $this->formatBytes($videoData['size']) . " ({$videoData['extension']})");

        // 2. Upload para GitHub
        $cdnUrl = $this->uploadToGitHub($videoData, $imdbId, $movie->title);

        if (!$cdnUrl) {
            $this->totalFailed++;
            $this->error("  ❌ Falha no upload para GitHub");
            return;
        }

        $this->info("  ✅ Upload realizado: {$cdnUrl}");

        // 3. Salvar URL no banco (apenas se for um filme real do banco)
        if (isset($movie->id) && $movie->id) {
            $movie->imdb_trailer_url = $cdnUrl;
            $movie->save();
            $this->info("  💾 URL salva no banco");
        } else {
            $this->info("  ℹ️  Modo teste - URL não salva no banco");
        }

        $this->totalSuccess++;
        Log::info("Trailer processado com sucesso para {$movie->title}: {$cdnUrl}");
    }

    /**
     * Baixa vídeo do trailer do IMDB
     *
     * Faz o download do trailer usando a API do IMDB, verifica o tamanho do arquivo,
     * comprime se necessário (para arquivos maiores que 20MB) e retorna os dados
     * preparados para upload.
     *
     * @param string $imdbId ID do filme no IMDB (formato ttXXXXXXX)
     * @return array|null Dados do vídeo ou null se falhar
     */
    private function downloadVideo(string $imdbId): ?array
    {
        $tempFile = null;

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

            // Ler arquivo e converter para base64 (otimizado para memória)
            $fileData = $this->readFileAsBase64($tempFile);
            $base64Data = $fileData;

            return [
                'data' => $base64Data,
                'extension' => $extension,
                'contentType' => $contentType,
                'size' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao baixar trailer para IMDB ID {$imdbId}: {$e->getMessage()}");
            return null;
        } finally {
            // Sempre apagar arquivo temporário
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Faz upload do vídeo para o GitHub
     *
     * Envia o vídeo comprimido para o repositório GitHub configurado e retorna
     * a URL da CDN para acesso público ao arquivo.
     *
     * @param array $videoData Dados do vídeo (conteúdo base64, extensão, etc.)
     * @param string $imdbId ID do filme no IMDB
     * @param string $movieTitle Título do filme para nome do arquivo
     * @return string|null URL da CDN ou null se falhar
     */
    private function uploadToGitHub(array $videoData, string $imdbId, string $movieTitle): ?string
    {
        try {
            $githubUser = env('GITHUB_USER');
            $repoName = env('GITHUB_REPO');
            $token = env('GITHUB_TOKEN');
            $folder = env('GITHUB_VIDEO_FOLDER');

            // Criar nome de arquivo limpo
            $cleanTitle = $this->sanitizeFileName($movieTitle);
            $fileName = "{$imdbId}-{$cleanTitle}.{$videoData['extension']}";

            $uploadUrl = "https://api.github.com/repos/{$githubUser}/{$repoName}/contents/{$folder}/{$fileName}";

            // Upload para GitHub
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-CineRadar',
            ])->put($uploadUrl, [
                'message' => "Upload trailer: {$movieTitle}",
                'content' => $videoData['data'],
            ]);

            if (!$response->successful()) {
                Log::error("Falha no upload para GitHub ({$imdbId}): HTTP {$response->status()} - {$response->body()}");
                return null;
            }

            // Construir URL da CDN
            $cdnUrl = "https://cdn.jsdelivr.net/gh/{$githubUser}/{$repoName}/{$folder}/{$fileName}";

            return $cdnUrl;

        } catch (\Exception $e) {
            Log::error("Erro ao fazer upload para GitHub ({$imdbId}): {$e->getMessage()}");
            return null;
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
     * Lê arquivo e converte para base64
     *
     * Lê o conteúdo completo do arquivo e o codifica em base64
     * para envio via API do GitHub.
     *
     * @param string $filePath Caminho para o arquivo a ser lido
     * @return string Conteúdo do arquivo em base64
     */
    private function readFileAsBase64(string $filePath): string
    {
        return base64_encode(file_get_contents($filePath));
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