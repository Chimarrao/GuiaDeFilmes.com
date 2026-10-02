<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MovieFactory extends Factory
{
    protected $model = \App\Models\Movie::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'tmdb_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'adult' => false,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1, 999999),
            'synopsis' => $this->faker->paragraph(),
            'imdb_synopsis' => null,
            'release_date' => $this->faker->dateTimeBetween('-5 years', '+1 year')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['upcoming', 'in_theaters', 'released']),
            'genres' => [$this->faker->randomElement(['Ação', 'Drama', 'Comédia', 'Terror'])],
            'poster_url' => 'https://image.tmdb.org/t/p/w500/' . $this->faker->uuid() . '.jpg',
            'imdb_poster_url' => null,
            'backdrop_url' => null,
            'trailer_url' => null,
            'imdb_trailer_url' => null,
            'cast' => [],
            'crew' => [],
            'videos' => [],
            'images' => [],
            'tmdb_rating' => $this->faker->randomFloat(1, 0, 10),
            'tmdb_vote_count' => $this->faker->numberBetween(0, 5000),
            'popularity' => $this->faker->randomFloat(3, 0, 500),
            'original_language' => 'en',
            'runtime' => $this->faker->numberBetween(60, 180),
            'budget' => 0,
            'revenue' => 0,
            'production_companies' => [],
            // Formato real do TMDB: array de objetos com iso_3166_1/name (não
            // array de strings) — warmupCountriesList() usa JSON_TABLE pra
            // extrair "$.name" de cada item, então precisa ser objeto.
            'production_countries' => [['iso_3166_1' => 'US', 'name' => 'United States of America']],
            'tagline' => $this->faker->sentence(),
            'where_to_watch' => [],
            'alternative_titles' => [],
            'external_ids' => [],
            'keywords' => [],
            'similar' => [],
            'justwatch_watch_info' => [],
        ];
    }
}
