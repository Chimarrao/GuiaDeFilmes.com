<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'synopsis' => $this->synopsis,
            'release_date' => $this->release_date,
            'status' => $this->status,
            'genres' => $this->genres,
            'poster_url' => $this->poster_url,
            'backdrop_url' => $this->backdrop_url,
            'tmdb_rating' => $this->tmdb_vote_count >= 50 ? $this->tmdb_rating : null,
            'tmdb_vote_count' => $this->tmdb_vote_count,
            'popularity' => $this->popularity,
            'runtime' => $this->runtime,
            'production_countries' => $this->production_countries,
            'tagline' => $this->tagline,
            // Campos pesados removidos: cast, crew, videos, images, similar, keywords
        ];
    }
}
