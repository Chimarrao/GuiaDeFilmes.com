<?php

use App\Models\Movie;

// Movies com título vazio pulam antes de chamar JustWatchController::search()
// (que faria shell_exec real) — usamos isso pra exercitar a lógica de
// seleção/filtro da query sem bater em rede nenhuma.

it('avisa e sai cedo quando não tem nenhum filme pra processar', function () {
    $this->artisan('justwatch:backfill --sleep=0')
        ->expectsOutputToContain('Nenhum filme para processar.')
        ->assertExitCode(0);
});

it('modo padrão pega só filmes com justwatch_watch_info NULL e tmdb_id preenchido', function () {
    $alvo = Movie::factory()->create(['title' => '', 'justwatch_watch_info' => null]);
    Movie::factory()->create(['title' => '', 'justwatch_watch_info' => []]);

    $this->artisan('justwatch:backfill --sleep=0')
        ->expectsOutputToContain('Total de filmes a processar: 1')
        ->assertExitCode(0);
});

it('--empty pega filmes com JSON vazio', function () {
    Movie::factory()->create(['title' => '', 'justwatch_watch_info' => null]);
    Movie::factory()->create(['title' => '', 'justwatch_watch_info' => []]);

    $this->artisan('justwatch:backfill --empty --sleep=0')
        ->expectsOutputToContain('Total de filmes a processar: 1')
        ->assertExitCode(0);
});

it('--year filtra por ano de lançamento', function () {
    Movie::factory()->create(['title' => '', 'release_date' => '2020-05-01']);
    Movie::factory()->create(['title' => '', 'release_date' => '2021-05-01']);

    $this->artisan('justwatch:backfill --year=2020 --sleep=0')
        ->expectsOutputToContain('Total de filmes a processar: 1')
        ->assertExitCode(0);
});

it('--start-id e --limit restringem a seleção', function () {
    $movies = Movie::factory()->count(3)->create(['title' => '', 'justwatch_watch_info' => null]);

    $startId = $movies->sortBy('id')->values()[1]->id;

    $this->artisan("justwatch:backfill --start-id={$startId} --limit=1 --sleep=0")
        ->expectsOutputToContain('Total de filmes a processar: 1')
        ->assertExitCode(0);
});

it('pula filme sem título sem chamar o JustWatch', function () {
    Movie::factory()->create(['title' => '', 'justwatch_watch_info' => null]);

    $this->artisan('justwatch:backfill --sleep=0')
        ->expectsOutputToContain('Sem título, ignorando.')
        ->assertExitCode(0);
});
