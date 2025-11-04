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

        // Caminho para o script Python
        $scriptPath = base_path('scripts/justwatch.py');

        // Comando para executar o Python
        $command = "python \"$scriptPath\" \"$query\"";
        if ($imdbId) {
            $command .= " \"$imdbId\"";
        }

        // Executar o comando
        $output = shell_exec($command);

        if ($output === null) {
            Log::error('Erro ao executar script Python: ' . $command);
            return response()->json(['error' => 'Erro interno do servidor'], 500);
        }

        // Limpar e decodificar JSON com suporte a UTF-8
        $output = trim($output);
        $data = json_decode($output, true, 512, JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Erro ao decodificar JSON do script Python: ' . json_last_error_msg() . ' | Output: ' . $output);
            return response()->json(['error' => 'Erro ao processar resposta'], 500);
        }

        return response()->json($data);
    }
}