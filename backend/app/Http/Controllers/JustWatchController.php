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

        if (!$query) {
            return response()->json(['error' => 'Nenhum título informado'], 400);
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
        if ($imdbId) {
            $command .= " \"$imdbId\"";
        }

        Log::info("Executando comando Python: " . $command);

        // Rodar o comando e capturar saída
        $output = shell_exec($command);

        if ($output === null) {
            Log::error('Erro ao executar script Python: ' . $command);
            return response()->json(['error' => 'Erro interno ao chamar Python'], 500);
        }

        // Decodificar JSON
        $output = trim($output);
        $data = json_decode($output, true, 512, JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Erro ao decodificar JSON: ' . json_last_error_msg() . ' | Output: ' . $output);
            return response()->json(['error' => 'Erro ao interpretar resposta do Python'], 500);
        }

        return response()->json($data);
    }
}
