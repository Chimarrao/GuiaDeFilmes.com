<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index($slug)
    {
        $movie = Movie::where('slug', $slug)->firstOrFail();
        return $movie->reviews;
    }
}