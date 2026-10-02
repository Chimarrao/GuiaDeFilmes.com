<?php

use App\Jobs\ProcessMovieTrailerJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Queue;

it('enfileira só filmes sem trailer nenhum (modo padrão)', function () {
    Queue::fake();

    $semTrailer = Movie::factory()->create(['trailer_url' => null, 'imdb_trailer_url' => null]);
    Movie::factory()->create(['trailer_url' => 'https://youtube.com/x']);

    $this->artisan('movies:queue-trailers')->assertExitCode(0);

    Queue::assertPushed(ProcessMovieTrailerJob::class, 1);
    Queue::assertPushed(fn (ProcessMovieTrailerJob $job) => $job->movieId === $semTrailer->id);
});

it('modo --reclaim enfileira só quem tem trailer local do backup e ainda não tem do youtube', function () {
    Queue::fake();

    $comTrailerLocal = Movie::factory()->create([
        'trailer_url' => null,
        'imdb_trailer_url' => 'https://guiadefilmes.com/trailers/abc.mp4',
    ]);
    Movie::factory()->create(['trailer_url' => null, 'imdb_trailer_url' => null]);

    $this->artisan('movies:queue-trailers --reclaim')->assertExitCode(0);

    Queue::assertPushed(ProcessMovieTrailerJob::class, 1);
    Queue::assertPushed(fn (ProcessMovieTrailerJob $job) => $job->movieId === $comTrailerLocal->id);
});

it('respeita o --limit', function () {
    Queue::fake();

    Movie::factory()->count(5)->create(['trailer_url' => null, 'imdb_trailer_url' => null]);

    $this->artisan('movies:queue-trailers --limit=2')->assertExitCode(0);

    Queue::assertPushed(ProcessMovieTrailerJob::class, 2);
});
