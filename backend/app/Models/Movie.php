<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'release_date',
        'status',
        'genres',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'cast',
        'crew',
        'videos',
        'images',
        'tmdb_rating',
        'tmdb_vote_count',
        'original_language',
        'runtime',
        'budget',
        'revenue',
        'production_companies',
        'production_countries',
        'tagline',
        'where_to_watch',
    ];

    protected $casts = [
        'genres' => 'array',
        'cast' => 'array',
        'crew' => 'array',
        'videos' => 'array',
        'images' => 'array',
        'production_companies' => 'array',
        'production_countries' => 'array',
        'where_to_watch' => 'array',
        'release_date' => 'date',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function aiContent()
    {
        return $this->hasOne(MovieAI::class);
    }
}