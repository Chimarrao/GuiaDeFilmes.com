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

    public function show($slug)
    {
        return Movie::where('slug', $slug)->with('reviews')->firstOrFail();
    }

    public function upcoming()
    {
        return Movie::where('status', 'upcoming')->paginate(20);
    }

    public function inTheaters()
    {
        return Movie::where('status', 'in_theaters')->paginate(20);
    }

    public function released()
    {
        return Movie::where('status', 'released')
            ->orderBy('release_date', 'desc')
            ->paginate(20);
    }
}