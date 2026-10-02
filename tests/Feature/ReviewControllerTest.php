<?php

use App\Http\Controllers\ReviewController;
use App\Models\Movie;
use App\Models\Review;

// Sem rota registrada pra esse controller (confirmado em routes/api.php e
// routes/web.php) — código morto/não exposto, mas testável diretamente.

it('devolve as reviews de um filme pelo slug', function () {
    $movie = Movie::factory()->create(['tmdb_id' => 999, 'slug' => 'filme-com-reviews']);
    Review::create(['movie_id' => 999, 'review_tmdb' => [['author' => 'Fulano', 'content' => 'Ótimo filme']]]);

    $reviews = (new ReviewController())->index('filme-com-reviews');

    expect($reviews)->toHaveCount(1);
    expect($reviews->first()->review_tmdb[0]['author'])->toBe('Fulano');
});

it('lança 404 pra slug de filme inexistente', function () {
    expect(fn () => (new ReviewController())->index('nao-existe'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
