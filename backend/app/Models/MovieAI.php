<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieAI extends Model
{
    use HasFactory;

    protected $table = 'movies_ai';

    protected $fillable = [
        'movie_id',
        'ai_synopsis',
        'seo_description',
        'reviews_summary',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
