<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'adult',
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
        'popularity',
        'original_language',
        'runtime',
        'budget',
        'revenue',
        'production_companies',
        'production_countries',
        'tagline',
        'where_to_watch',
        'alternative_titles',
        'external_ids',
        'keywords',
        'similar',
        'justwatch_watch_info',
    ];

    protected $casts = [
        'adult' => 'boolean',
        'genres' => 'array',
        'cast' => 'array',
        'crew' => 'array',
        'videos' => 'array',
        'images' => 'array',
        'production_companies' => 'array',
        'production_countries' => 'array',
        'where_to_watch' => 'array',
        'alternative_titles' => 'array',
        'external_ids' => 'array',
        'keywords' => 'array',
        'similar' => 'array',
        'release_date' => 'date',
    ];

    /**
     * Set a given attribute on the model with proper JSON encoding
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, ['genres', 'cast', 'crew', 'videos', 'images', 'production_companies', 'production_countries', 'where_to_watch', 'alternative_titles', 'external_ids', 'keywords', 'similar'])) {
            $this->attributes[$key] = is_array($value) 
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) 
                : $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    // Relacionamentos comentados pois as tabelas foram removidas
    // public function reviews()
    // {
    //     return $this->hasMany(Review::class);
    // }

    // public function aiContent()
    // {
    //     return $this->hasOne(MovieAI::class);
    // }
}