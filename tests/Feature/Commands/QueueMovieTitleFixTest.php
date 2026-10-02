<?php

use App\Jobs\FixMovieTitleJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('enfileira só filmes com idioma original diferente de en/pt, mais populares primeiro', function () {
    Queue::fake();

    $hindi = Movie::factory()->create(['original_language' => 'hi', 'popularity' => 50]);
    Movie::factory()->create(['original_language' => 'en']);
    Movie::factory()->create(['original_language' => 'pt']);

    $this->artisan('movies:queue-title-fix')->assertExitCode(0);

    Queue::assertPushed(FixMovieTitleJob::class, 1);
    Queue::assertPushed(fn (FixMovieTitleJob $job) => $job->movieId === $hindi->id);
});

it('respeita o --limit', function () {
    Queue::fake();

    Movie::factory()->count(5)->create(['original_language' => 'hi']);

    $this->artisan('movies:queue-title-fix --limit=2')->assertExitCode(0);

    Queue::assertPushed(FixMovieTitleJob::class, 2);
});

it('continua o lote mesmo se um filme der 404 no TMDB (regressão)', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/1*' => Http::response(null, 404),
        'api.themoviedb.org/3/movie/2*' => Http::response(['title' => 'Guerra 2', 'original_title' => 'War 2'], 200),
    ]);

    $comErro = Movie::factory()->create(['tmdb_id' => 1, 'title' => 'War 1', 'original_language' => 'hi']);
    $comSucesso = Movie::factory()->create(['tmdb_id' => 2, 'title' => 'War 2', 'original_language' => 'hi']);

    // QUEUE_CONNECTION=sync roda os jobs inline aqui mesmo. Antes da correção,
    // o 404 do primeiro filme derrubava o comando inteiro (Http::retry()
    // lança exceção por padrão em resposta não-2xx) e o segundo filme nunca
    // era processado.
    $this->artisan('movies:queue-title-fix')->assertExitCode(0);

    expect($comErro->fresh()->title)->toBe('War 1');
    expect($comSucesso->fresh()->title)->toBe('Guerra 2');
});
