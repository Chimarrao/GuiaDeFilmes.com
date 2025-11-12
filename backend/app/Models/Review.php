<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Review - Armazena avaliações de filmes vindas do TMDB
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'review_tmdb',
    ];

    protected $casts = [
        'review_tmdb' => 'array',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}