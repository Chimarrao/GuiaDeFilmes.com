<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'adult',
        'also_known_as',
        'biography',
        'birthday',
        'deathday',
        'gender',
        'homepage',
        'imdb_id',
        'known_for_department',
        'name',
        'place_of_birth',
        'popularity',
        'profile_path',
    ];

    protected $casts = [
        'adult' => 'boolean',
        'also_known_as' => 'array',
        'birthday' => 'date',
        'deathday' => 'date',
    ];
}
