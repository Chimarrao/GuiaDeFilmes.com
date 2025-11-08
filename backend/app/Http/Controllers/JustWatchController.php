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
            // Se for formato completo (2012-10-16), pega os primeiros 4 dígitos
            // Se for só ano (2012), usa direto
            $year = substr($releaseDate, 0, 4);
        }

        // Caminho do script Python
        $scriptPath = base_path('scripts/justwatch.py');

        // Detectar ambiente (Windows x Linux)
        $isWindows = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';

        if ($isWindows) {
            // Ambiente local no Windows
            $python = 'python'; // ou python3 dependendo da sua máquina
        } else {
            // Ambiente Linux (servidor)
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
        
        // Adicionar IMDB ID se fornecido (mesmo que vazio para manter ordem dos parâmetros)
        if ($imdbId) {
            $command .= " \"$imdbId\"";
        } else {
            $command .= " \"\""; // Parâmetro vazio para manter ordem
        }
        
        // Adicionar ano se fornecido
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

        // Limpar e garantir UTF-8 válido
        $output = trim($output);
        
        // Detectar e converter encoding se necessário
        if (!mb_check_encoding($output, 'UTF-8')) {
            // Tentar detectar o encoding
            $encoding = mb_detect_encoding($output, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $output = mb_convert_encoding($output, 'UTF-8', $encoding);
            } else {
                // Força conversão de ISO-8859-1 para UTF-8 como fallback
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
