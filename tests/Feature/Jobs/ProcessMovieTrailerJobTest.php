<?php

use App\Jobs\ProcessMovieTrailerJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

function tmdbVideosResponse(array $results): array
{
    return ['results' => $results];
}

it('usa o trailer oficial do YouTube em pt-BR quando existe', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/*/videos*language=pt-BR*' => Http::response(tmdbVideosResponse([
            ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'PTBR123'],
        ])),
    ]);

    $movie = Movie::factory()->create(['tmdb_id' => 1, 'trailer_url' => null, 'imdb_trailer_url' => null]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    expect($movie->fresh()->trailer_url)->toBe('https://www.youtube.com/watch?v=PTBR123');
});

it('cai pro en-US quando não tem trailer oficial em pt-BR', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/*/videos*language=pt-BR*' => Http::response(tmdbVideosResponse([])),
        'api.themoviedb.org/3/movie/*/videos*language=en-US*' => Http::response(tmdbVideosResponse([
            ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'ENUS456'],
        ])),
    ]);

    $movie = Movie::factory()->create(['tmdb_id' => 2, 'trailer_url' => null, 'imdb_trailer_url' => null]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    expect($movie->fresh()->trailer_url)->toBe('https://www.youtube.com/watch?v=ENUS456');
});

it('libera o trailer alternativo local quando acha um real no YouTube', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/*/videos*' => Http::response(tmdbVideosResponse([
            ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'REAL789'],
        ])),
    ]);

    File::ensureDirectoryExists(public_path('trailers'));
    File::put(public_path('trailers/local-antigo.mp4'), 'conteudo falso');

    $movie = Movie::factory()->create([
        'tmdb_id' => 3,
        'trailer_url' => null,
        'imdb_trailer_url' => rtrim(config('app.url'), '/') . '/trailers/local-antigo.mp4',
    ]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    expect($movie->fresh()->trailer_url)->toBe('https://www.youtube.com/watch?v=REAL789');
    expect($movie->fresh()->imdb_trailer_url)->toBeNull();
    expect(File::exists(public_path('trailers/local-antigo.mp4')))->toBeFalse();
});

it('não faz nada se o filme já tem algum trailer e não achou nenhum no YouTube', function () {
    Http::fake(['api.themoviedb.org/*' => Http::response(tmdbVideosResponse([]))]);

    $movie = Movie::factory()->create([
        'tmdb_id' => 4,
        'trailer_url' => 'https://www.youtube.com/watch?v=jahaexiste',
        'imdb_trailer_url' => null,
    ]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    expect($movie->fresh()->trailer_url)->toBe('https://www.youtube.com/watch?v=jahaexiste');
});

it('baixa trailer alternativo quando não acha nenhum no YouTube e não tem trailer nenhum', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(tmdbVideosResponse([])),
        'imdb.iamidiotareyoutoo.com/*' => Http::response('conteudo de video falso', 200),
    ]);

    $movie = Movie::factory()->create([
        'tmdb_id' => 5,
        'trailer_url' => null,
        'imdb_trailer_url' => null,
        'external_ids' => ['imdb_id' => 'tt9999999'],
    ]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    $fresh = $movie->fresh();
    expect($fresh->imdb_trailer_url)->not->toBeNull();
    expect($fresh->imdb_trailer_url)->toContain('/trailers/tt9999999-');

    $downloadedPath = public_path('trailers/' . basename(parse_url($fresh->imdb_trailer_url, PHP_URL_PATH)));
    expect(File::exists($downloadedPath))->toBeTrue();
    File::delete($downloadedPath);
});

it('não faz nada se não tem imdb_id pra tentar o download alternativo', function () {
    Http::fake(['api.themoviedb.org/*' => Http::response(tmdbVideosResponse([]))]);

    $movie = Movie::factory()->create([
        'tmdb_id' => 6,
        'trailer_url' => null,
        'imdb_trailer_url' => null,
        'external_ids' => [],
    ]);

    (new ProcessMovieTrailerJob($movie->id))->handle();

    expect($movie->fresh()->imdb_trailer_url)->toBeNull();
});

it('não faz nada se o filme não existir ou não tiver tmdb_id', function () {
    Http::fake();

    (new ProcessMovieTrailerJob(999999))->handle();

    $movie = Movie::factory()->create(['tmdb_id' => null]);
    (new ProcessMovieTrailerJob($movie->id))->handle();

    Http::assertNothingSent();
});
