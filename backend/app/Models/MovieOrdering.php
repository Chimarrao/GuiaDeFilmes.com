<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieOrdering extends Model
{
    use HasFactory;

    protected $fillable = [
        'in_theaters',
        'upcoming',
        'released',
    ];

    protected $casts = [
        'in_theaters' => 'array',
        'upcoming' => 'array',
        'released' => 'array',
    ];
}
