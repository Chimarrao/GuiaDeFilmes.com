<?php

namespace App\Http\Controllers;

use App\Models\MovieOrdering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieOrderingController extends Controller
{
    /**
     * Obter ordenação customizada para um tipo específico
     * 
     * Retorna a lista de filmes com ordenação manual definida para o tipo especificado.
     * Se não houver ordenação, retorna array vazio.
     * 
     * @param string $type Tipo da ordenação: 'in_theaters', 'upcoming' ou 'released'
     * @return \Illuminate\Http\JsonResponse
     * 
     * @example GET /api/movie-ordering/in_theaters
     * Response: {"type": "in_theaters", "ordering": [{"id_tmdb": 123, "title": "Filme"}]}
     */
    public function getOrdering($type)
    {
        $validTypes = ['in_theaters', 'upcoming', 'released'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $ordering = MovieOrdering::first();
        
        if (!$ordering) {
            return response()->json(['error' => 'Ordering not found'], 404);
        }

        return response()->json([
            'type' => $type,
            'ordering' => $ordering->$type ?? []
        ]);
    }

    /**
     * Atualizar ordenação customizada para um tipo específico
     * 
     * Recebe array de filmes e salva como ordenação manual. Geralmente usado por
     * automação (n8n) ou painel administrativo.
     * 
     * VALIDAÇÃO:
     * - Array obrigatório em 'ordering'
     * - Cada item deve ter: id_tmdb (int), title (string)
     * 
     * COMPORTAMENTO:
     * - Se MovieOrdering não existe, cria automaticamente
     * - Substitui COMPLETAMENTE a ordenação anterior do tipo
     * - Outros tipos não são afetados
     * 
     * @param \Illuminate\Http\Request $request Request com campo 'ordering' (array)
     * @param string $type Tipo da ordenação: 'in_theaters', 'upcoming' ou 'released'
     * @return \Illuminate\Http\JsonResponse
     * 
     * @example POST /api/movie-ordering/upcoming
     * Body: {"ordering": [{"id_tmdb": 456, "title": "Novo Filme"}]}
     * Response: {"success": true, "type": "upcoming", "count": 1}
     */
    public function updateOrdering(Request $request, $type)
    {
        $validTypes = ['in_theaters', 'upcoming', 'released'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $validator = Validator::make($request->all(), [
            'ordering' => 'required|array',
            'ordering.*.id_tmdb' => 'required|integer',
            'ordering.*.title' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $ordering = MovieOrdering::first();
        
        if (!$ordering) {
            $ordering = MovieOrdering::create([
                'in_theaters' => [],
                'upcoming' => [],
                'released' => [],
            ]);
        }

        $ordering->$type = $request->ordering;
        $ordering->save();

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => count($request->ordering),
            'message' => 'Ordering updated successfully'
        ]);
    }

    /**
     * Obter todas as ordenações customizadas
     * 
     * Retorna um objeto com as 3 ordenações (in_theaters, upcoming, released).
     * Se MovieOrdering não existe, retorna arrays vazios para todos os tipos.
     * 
     * Útil para visualizar/editar todas as ordenações de uma vez.
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * @example GET /api/movie-ordering/all
     * Response: {
     *   "in_theaters": [...],
     *   "upcoming": [...],
     *   "released": [...]
     * }
     */
    public function getAllOrderings()
    {
        $ordering = MovieOrdering::first();
        
        if (!$ordering) {
            return response()->json([
                'in_theaters' => [],
                'upcoming' => [],
                'released' => []
            ]);
        }

        return response()->json([
            'in_theaters' => $ordering->in_theaters ?? [],
            'upcoming' => $ordering->upcoming ?? [],
            'released' => $ordering->released ?? []
        ]);
    }
}
