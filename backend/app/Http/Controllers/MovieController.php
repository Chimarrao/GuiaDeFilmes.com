<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Http\Resources\MovieListResource;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return MovieListResource::collection($query->paginate(20));
    }

    public function search(Request $request)
    {
        $searchTerm = $request->input('q', '');
        
        if (empty($searchTerm)) {
            return response()->json(['data' => []]);
        }

        $limit = $request->input('limit', 20);

        // Normaliza o termo de busca - separa cada palavra
        $searchWords = explode(' ', strtolower($searchTerm));

        $movies = Movie::where(function($query) use ($searchTerm, $searchWords) {
            // Busca pelo termo completo
            $query->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('original_title', 'like', "%{$searchTerm}%")
                  ->orWhere('synopsis', 'like', "%{$searchTerm}%")
                  ->orWhere('tagline', 'like', "%{$searchTerm}%");
            
            // Busca por cada palavra individualmente
            foreach ($searchWords as $word) {
                if (strlen($word) >= 3) { // Apenas palavras com 3+ caracteres
                    $query->orWhere('title', 'like', "%{$word}%")
                          ->orWhere('original_title', 'like', "%{$word}%");
                }
            }
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

        return MovieListResource::collection($movies);
    }

    public function show($slug)
    {
        // MovieDetail retorna tudo, incluindo reviews
        $movie = Movie::with('reviews')->where('slug', $slug)->firstOrFail();
        
        // Processa as reviews para retornar os dados do JSON
        if ($movie->reviews->isNotEmpty()) {
            $allReviews = [];
            foreach ($movie->reviews as $review) {
                if (is_array($review->review_tmdb) && !empty($review->review_tmdb)) {
                    $allReviews = array_merge($allReviews, $review->review_tmdb);
                }
            }
            $movie->reviews_data = $allReviews;
        } else {
            $movie->reviews_data = [];
        }
        
        return $movie;
    }

    public function upcoming(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $movies = Movie::where('release_date', '>', now())
            ->orderBy('release_date', 'asc')
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    public function inTheaters(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $movies = Movie::whereBetween('release_date', [
                now()->subDays(30),
                now()
            ])
            ->orderBy('release_date', 'desc')
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    public function released(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $movies = Movie::whereBetween('release_date', [
                now()->subDays(30),
                now()->subDays(7)
            ])
            ->orderBy('release_date', 'desc')
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
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
        
        $movies = Movie::whereRaw("LOWER(genres) LIKE ?", ['%' . strtolower($genreName) . '%'])
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
    }

    public function byDecade($decade, Request $request)
    {
        $limit = $request->input('limit', 20);
        $startYear = intval($decade);
        
        if ($startYear < 1960) {
            // Classics: before 1960
            $movies = Movie::whereNotNull('release_date')
                ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) < ?", [1960])
                ->orderBy('popularity', 'desc')
                ->paginate($limit);
                
            return MovieListResource::collection($movies);
        }
        
        $endYear = $startYear + 9;
        
        $movies = Movie::whereNotNull('release_date')
            ->whereRaw("CAST(substr(release_date, 1, 4) AS UNSIGNED) BETWEEN ? AND ?", [$startYear, $endYear])
            ->orderBy('popularity', 'desc')
            ->paginate($limit);
            
        return MovieListResource::collection($movies);
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
            $query->where('tmdb_rating', '>=', floatval($request->minRating));
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
        $movies = $query->paginate($limit);
        
        return MovieListResource::collection($movies);
    }
}