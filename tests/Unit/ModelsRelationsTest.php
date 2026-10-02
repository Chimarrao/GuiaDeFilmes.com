<?php

use App\Models\Movie;
use App\Models\Review;

// App\Models\MovieAI não tem teste aqui de propósito: a tabela "movies_ai"
// que ele mapeia foi criada e depois DROPADA numa migration de limpeza
// posterior (2025_11_01_015742_update_movies_table_add_fields_and_cleanup)
// — é um model órfão, usá-lo em produção hoje geraria erro de tabela
// inexistente. Fora do escopo deste PR remover o model morto.

it('Movie::reviews() relaciona pelo tmdb_id', function () {
    $movie = Movie::factory()->create(['tmdb_id' => 777]);
    Review::create(['movie_id' => 777, 'review_tmdb' => [['author' => 'X']]]);

    expect($movie->reviews)->toHaveCount(1);
});

it('Review::movie() relaciona de volta pro filme', function () {
    $movie = Movie::factory()->create(['id' => 888]);
    $review = Review::create(['movie_id' => 888, 'review_tmdb' => []]);

    expect($review->movie->id)->toBe($movie->id);
});

it('Movie::setAttribute passa direto valores não-array pros campos JSON (ex: null)', function () {
    $movie = Movie::factory()->create();
    $movie->where_to_watch = null;
    $movie->save();

    expect($movie->fresh()->where_to_watch)->toBeNull();
});
