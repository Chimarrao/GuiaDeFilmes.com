<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JustWatchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        $imdbId = $request->input('imdb_id');
        $releaseDate = $request->input('release_date'); // Formato: YYYY-MM-DD ou YYYY

        if (!$query) {
            return response()->json(['error' => 'Nenhum título informado'], 400);
        }

        // Extrair ano da data de lançamento se fornecida
        $year = null;
        if ($releaseDate) {
            $year = substr($releaseDate, 0, 4);
        }

        $scriptPath = base_path('scripts/justwatch.py');

        $isWindows = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';

        if ($isWindows) {
            $python = 'python';
        } else {
            $venvPython = base_path('venv/bin/python3');

            // Se o venv existir, usa ele — senão usa python3 global
            if (file_exists($venvPython)) {
                $python = escapeshellcmd($venvPython);
            } else {
                $python = 'python3';
            }
        }

        // Montar comando
        $command = "$python \"$scriptPath\" \"$query\"";
        
        if ($imdbId) {
            $command .= " \"$imdbId\"";
        } else {
            // Parâmetro vazio para manter ordem
            $command .= " \"\"";
        }

        if ($year) {
            $command .= " \"$year\"";
        }

        // No Windows, adicionar comando para UTF-8
        if ($isWindows) {
            $command = "chcp 65001 > nul && " . $command;
        }

        Log::info("Executando comando Python: " . $command);

        // Rodar o comando e capturar saída
        $output = shell_exec($command);

        if ($output === null) {
            Log::error('Erro ao executar script Python: ' . $command);
            return response()->json(['error' => 'Erro interno ao chamar Python'], 500);
        }

        $output = trim($output);
        
        /**
         * NORMALIZAÇÃO DE ENCODING UTF-8
         * 
         * PROBLEMA:
         * Scripts Python no Windows podem retornar dados em encodings diferentes
         * (Windows-1252, ISO-8859-1) mesmo quando configuramos UTF-8 no código.
         * Isso causa caracteres corrompidos: "açúcar" vira "aÃ§Ãºcar".
         * 
         * SOLUÇÃO EM 3 ETAPAS:
         * 
         * 1. DETECTAR ENCODING INCORRETO:
         *    mb_check_encoding() verifica se string já está em UTF-8 válido.
         *    Se não estiver, precisamos converter.
         * 
         * 2. IDENTIFICAR ENCODING ORIGINAL:
         *    mb_detect_encoding() tenta descobrir o encoding original:
         *    - Windows-1252 (padrão Windows português/inglês)
         *    - ISO-8859-1 (Latin1, comum em sistemas antigos)
         *    - ASCII (subset de UTF-8, sem acentos)
         * 
         * 3. CONVERTER PARA UTF-8:
         *    mb_convert_encoding($output, 'UTF-8', $encodingOriginal)
         *    Converte da codificação original para UTF-8.
         * 
         * FALLBACK:
         * Se não conseguir detectar, usa utf8_encode() (assume ISO-8859-1).
         * 
         * LIMPEZA FINAL:
         * mb_convert_encoding($output, 'UTF-8', 'UTF-8') remove qualquer
         * caractere inválido UTF-8 restante (bytes corrompidos).
         * 
         * RESULTADO:
         * String 100% UTF-8 válida, pronta para json_decode().
         */
        if (!mb_check_encoding($output, 'UTF-8')) {
            $encoding = mb_detect_encoding($output, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $output = mb_convert_encoding($output, 'UTF-8', $encoding);
            } else {
                $output = utf8_encode($output);
            }
        }

        // Remove caracteres inválidos UTF-8 (se ainda houver)
        $output = mb_convert_encoding($output, 'UTF-8', 'UTF-8');

        // Decodificar JSON
        $data = json_decode($output, true, 512, JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Erro ao decodificar JSON: ' . json_last_error_msg() . ' | Output: ' . substr($output, 0, 500));
            return response()->json(['error' => 'Erro ao interpretar resposta do Python'], 500);
        }

        return response()->json($data);
    }
}
