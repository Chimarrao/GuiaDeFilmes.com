<?php

use App\Jobs\FixMovieTitleJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

it('corrige o título quando o atual é igual ao original e existe tradução pt-BR', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['title' => 'Guerra 2', 'original_title' => 'War 2']),
    ]);

    $movie = Movie::factory()->create(['tmdb_id' => 1109086, 'title' => 'War 2']);

    (new FixMovieTitleJob($movie->id))->handle();

    expect($movie->fresh()->title)->toBe('Guerra 2');
});

it('não mexe no título se ele já foi corrigido antes (não é mais igual ao original)', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['title' => 'Guerra 2', 'original_title' => 'War 2']),
    ]);

    $movie = Movie::factory()->create(['tmdb_id' => 1109086, 'title' => 'Guerra 2']);

    (new FixMovieTitleJob($movie->id))->handle();

    expect($movie->fresh()->title)->toBe('Guerra 2');
});

it('não mexe no título quando não existe tradução pt-BR diferente do original', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['title' => 'War 2', 'original_title' => 'War 2']),
    ]);

    $movie = Movie::factory()->create(['tmdb_id' => 1109086, 'title' => 'War 2']);

    (new FixMovieTitleJob($movie->id))->handle();

    expect($movie->fresh()->title)->toBe('War 2');
});

it('não faz nada se o filme não existir mais', function () {
    Http::fake();

    (new FixMovieTitleJob(999999))->handle();

    Http::assertNothingSent();
});

it('não faz nada se o filme não tiver tmdb_id', function () {
    Http::fake();

    $movie = Movie::factory()->create(['tmdb_id' => null]);

    (new FixMovieTitleJob($movie->id))->handle();

    Http::assertNothingSent();
});

it('não faz nada se a resposta do TMDB não for bem-sucedida', function () {
    Http::fake(['api.themoviedb.org/*' => Http::response(null, 500)]);

    $movie = Movie::factory()->create(['tmdb_id' => 1109086, 'title' => 'War 2']);

    (new FixMovieTitleJob($movie->id))->handle();

    expect($movie->fresh()->title)->toBe('War 2');
});
