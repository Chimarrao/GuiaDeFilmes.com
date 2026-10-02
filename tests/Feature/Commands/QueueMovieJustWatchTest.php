<?php

use App\Jobs\ProcessMovieJustWatchJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Queue;

it('enfileira só filmes nunca processados (modo padrão)', function () {
    Queue::fake();

    $nuncaProcessado = Movie::factory()->create(['justwatch_watch_info' => null]);
    Movie::factory()->create(['justwatch_watch_info' => [['platform' => 'Netflix']]]);

    $this->artisan('movies:queue-justwatch')->assertExitCode(0);

    Queue::assertPushed(ProcessMovieJustWatchJob::class, 1);
    Queue::assertPushed(fn (ProcessMovieJustWatchJob $job) => $job->movieId === $nuncaProcessado->id);
});

it('ignora filmes adultos', function () {
    Queue::fake();

    Movie::factory()->create(['justwatch_watch_info' => null, 'adult' => true]);

    $this->artisan('movies:queue-justwatch')->assertExitCode(0);

    Queue::assertNotPushed(ProcessMovieJustWatchJob::class);
});

it('modo --empty reprocessa quem ficou com array vazio, mais populares primeiro', function () {
    Queue::fake();

    $vazioPopular = Movie::factory()->create(['justwatch_watch_info' => [], 'popularity' => 90]);
    $vazioMenosPopular = Movie::factory()->create(['justwatch_watch_info' => [], 'popularity' => 10]);
    Movie::factory()->create(['justwatch_watch_info' => null]);

    $this->artisan('movies:queue-justwatch --empty')->assertExitCode(0);

    Queue::assertPushed(ProcessMovieJustWatchJob::class, 2);
});
