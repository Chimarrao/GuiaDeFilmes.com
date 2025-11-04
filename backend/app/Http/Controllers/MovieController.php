<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->paginate(20);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->input('q', '');
        
        if (empty($searchTerm)) {
            return response()->json(['data' => []]);
        }

        $limit = $request->input('limit', 20);

        $movies = Movie::where(function($query) use ($searchTerm) {
            // Search in title
            $query->where('title', 'like', "%{$searchTerm}%")
                  // Search in synopsis
                  ->orWhere('synopsis', 'like', "%{$searchTerm}%")
                  // Search in original title
                  ->orWhere('original_title', 'like', "%{$searchTerm}%")
                  // Search in tagline
                  ->orWhere('tagline', 'like', "%{$searchTerm}%");
        })
        // Also search in JSON fields (cast, crew, genres)
        ->orWhereRaw("LOWER(cast) LIKE ?", ['%' . strtolower($searchTerm) . '%'])
        ->orWhereRaw("LOWER(crew) LIKE ?", ['%' . strtolower($searchTerm) . '%'])
        ->orWhereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($searchTerm) . '%'])
        ->orderByRaw("
            CASE 
                WHEN title LIKE ? THEN 1
                WHEN original_title LIKE ? THEN 2
                WHEN tagline LIKE ? THEN 3
                WHEN synopsis LIKE ? THEN 4
                ELSE 5
            END
        ", [
            "{$searchTerm}%",
            "{$searchTerm}%",
            "%{$searchTerm}%",
            "%{$searchTerm}%"
        ])
        ->paginate($limit);

        return $movies;
    }

    public function show($slug)
    {
        return Movie::where('slug', $slug)->firstOrFail();
    }

    public function upcoming(Request $request)
    {
        $limit = $request->input('limit', 20);
        return Movie::where('status', 'upcoming')->paginate($limit);
    }

    public function inTheaters(Request $request)
    {
        $limit = $request->input('limit', 20);
        return Movie::where('status', 'in_theaters')->paginate($limit);
    }

    public function released(Request $request)
    {
        $limit = $request->input('limit', 20);
        return Movie::where('status', 'released')
            ->orderBy('release_date', 'desc')
            ->paginate($limit);
    }

    public function byGenre($genre, Request $request)
    {
        $limit = $request->input('limit', 20);
        
        // Map slugs to genre names
        $genreMap = [
            'acao' => 'Ação',
            'aventura' => 'Aventura',
            'comedia' => 'Comédia',
            'drama' => 'Drama',
            'ficcao-cientifica' => 'Ficção científica',
            'terror' => 'Terror',
            'romance' => 'Romance',
            'suspense' => 'Suspense',
            'animacao' => 'Animação',
            'crime' => 'Crime',
            'documentario' => 'Documentário',
            'familia' => 'Família',
            'fantasia' => 'Fantasia',
            'guerra' => 'Guerra',
            'historia' => 'História',
            'misterio' => 'Mistério',
            'musical' => 'Musical',
            'western' => 'Western',
        ];
        
        $genreName = $genreMap[$genre] ?? $genre;
        
        return Movie::whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%'])
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
    }

    public function byDecade($decade, Request $request)
    {
        $limit = $request->input('limit', 20);
        $startYear = intval($decade);
        
        if ($startYear < 1960) {
            // Classics: before 1960
            return Movie::whereNotNull('release_date')
                ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) < ?", [1960])
                ->orderBy('popularity', 'desc')
                ->paginate($limit);
        }
        
        $endYear = $startYear + 9;
        
        return Movie::whereNotNull('release_date')
            ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
    }

    public function filter(Request $request)
    {
        $query = Movie::query();
        
        // Genre filter
        if ($request->filled('genre')) {
            $genreMap = [
                'acao' => 'Ação',
                'aventura' => 'Aventura',
                'comedia' => 'Comédia',
                'drama' => 'Drama',
                'ficcao-cientifica' => 'Ficção científica',
                'terror' => 'Terror',
                'romance' => 'Romance',
                'suspense' => 'Suspense',
                'animacao' => 'Animação',
                'crime' => 'Crime',
                'documentario' => 'Documentário',
                'familia' => 'Família',
                'fantasia' => 'Fantasia',
                'guerra' => 'Guerra',
                'historia' => 'História',
                'misterio' => 'Mistério',
                'musical' => 'Musical',
                'western' => 'Western',
            ];
            
            $genreName = $genreMap[$request->genre] ?? $request->genre;
            $query->whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%']);
        }
        
        // Year range filter
        if ($request->filled('yearFrom')) {
            $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) >= ?", [intval($request->yearFrom)]);
        }
        
        if ($request->filled('yearTo')) {
            $query->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) <= ?", [intval($request->yearTo)]);
        }
        
        // Rating filter
        if ($request->filled('minRating')) {
            $query->where('rating', '>=', floatval($request->minRating));
        }
        
        // Country filter
        if ($request->filled('country')) {
            $countryName = $request->country;
            
            // Filtra para mostrar apenas filmes que têm EXATAMENTE 1 país
            // Verifica se production_countries é um array JSON com apenas 1 elemento
            // e se esse elemento contém o país procurado
            $query->whereRaw("JSON_LENGTH(production_countries) = 1")
                  ->whereRaw("LOWER(production_countries) LIKE ?", ['%' . strtolower($countryName) . '%']);
        }
        
        // Language filter
        if ($request->filled('language')) {
            $query->where('original_language', $request->language);
        }
        
        // Sorting
        $sortBy = $request->input('sortBy', 'popularity');
        switch ($sortBy) {
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'release_date':
                $query->orderBy('release_date', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('popularity', 'desc');
        }
        
        $limit = $request->input('limit', 20);
        return $query->paginate($limit);
    }
}