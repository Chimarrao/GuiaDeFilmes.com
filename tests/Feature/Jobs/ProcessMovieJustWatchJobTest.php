<?php

use App\Jobs\ProcessMovieJustWatchJob;
use App\Models\Movie;

// O caminho feliz chama JustWatchController::search(), que faz shell_exec()
// de um script Python batendo na API real do JustWatch — não dá pra testar
// isso de forma rápida/confiável aqui (mesma limitação do JustWatchApiTest).
// Cobrimos os retornos antecipados, que são puramente determinísticos.

it('não faz nada se o filme não existir mais', function () {
    (new ProcessMovieJustWatchJob(999999))->handle();

    expect(true)->toBeTrue();
});

it('não faz nada se o filme não tiver título', function () {
    $movie = Movie::factory()->create(['title' => '']);

    (new ProcessMovieJustWatchJob($movie->id))->handle();

    expect($movie->fresh()->justwatch_watch_info)->toBe([]);
});
