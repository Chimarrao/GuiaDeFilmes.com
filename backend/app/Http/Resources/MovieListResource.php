<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para transformação de dados de filmes em listagens
 * 
 * PROPÓSITO:
 * Esta classe é um "transformador" que converte dados do Model Movie
 * em um formato JSON otimizado para LISTAGENS (index, search, upcoming, etc).
 * 
 */
class MovieListResource extends JsonResource
{
    /**
     * Transforma o model Movie em array JSON otimizado para listagens
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed> Array com campos essenciais do filme
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
            
            // Rating condicional: apenas se tiver 50+ votos (qualidade mínima)
            'tmdb_rating' => $this->tmdb_vote_count >= 50 ? $this->tmdb_rating : null,
            'tmdb_vote_count' => $this->tmdb_vote_count,
            'popularity' => $this->popularity,
            'runtime' => $this->runtime,
            'production_countries' => $this->production_countries,
            'tagline' => $this->tagline,
            
            // Campos pesados REMOVIDOS para performance:
            // cast, crew, videos, images, similar, keywords
            // (Esses só aparecem no endpoint de detalhes
        ];
    }
}
