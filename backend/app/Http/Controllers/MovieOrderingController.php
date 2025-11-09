<?php

namespace App\Http\Controllers;

use App\Models\MovieOrdering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieOrderingController extends Controller
{
    /**
     * Obter ordenação atual para um tipo específico
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
     * Atualizar ordenação para um tipo específico (usado pelo n8n)
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
     * Obter todas as ordenações
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
