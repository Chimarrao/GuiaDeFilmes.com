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
        return Movie::where('slug', $slug)->with('reviews')->firstOrFail();
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
}