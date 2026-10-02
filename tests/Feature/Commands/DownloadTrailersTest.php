<?php

use App\Models\Movie;
use Illuminate\Support\Facades\Http;

function fakeVideoBody(int $megabytes): string
{
    return str_repeat('x', $megabytes * 1024 * 1024 + 100);
}

afterEach(function () {
    // Qualquer trailer real gravado em public/trailers durante o teste é
    // cruft local — limpa pra não sujar o disco de dev a cada rodada.
    foreach (glob(public_path('trailers/tt*')) ?: [] as $file) {
        @unlink($file);
    }
});

it('avisa e sai cedo quando não tem filme elegível', function () {
    $this->artisan('trailers:download')
        ->expectsOutputToContain('Nenhum filme encontrado para processar.')
        ->assertExitCode(0);
});

it('ignora filme sem imdb_id', function () {
    Movie::factory()->create([
        'external_ids' => ['tvdb_id' => 123], // sem imdb_id
        'trailer_url' => null,
        'imdb_trailer_url' => null,
    ]);

    $this->artisan('trailers:download')->assertExitCode(0);
});

it('baixa e salva o trailer localmente quando o download dá certo', function () {
    Http::fake([
        'imdb.iamidiotareyoutoo.com/*' => Http::response(fakeVideoBody(6), 200, ['Content-Type' => 'video/mp4']),
    ]);

    $movie = Movie::factory()->create([
        'external_ids' => ['imdb_id' => 'tt7654321'],
        'trailer_url' => null,
        'imdb_trailer_url' => null,
    ]);

    $this->artisan('trailers:download')->assertExitCode(0);

    $fresh = $movie->fresh();
    expect($fresh->imdb_trailer_url)->toContain('/trailers/tt7654321-');
    expect($fresh->imdb_trailer_url)->toEndWith('.mp4');

    $path = public_path(parse_url($fresh->imdb_trailer_url, PHP_URL_PATH));
    expect(File::exists($path))->toBeTrue();
});

it('marca imdb_trailer_url vazio quando o download falha', function () {
    Http::fake(['imdb.iamidiotareyoutoo.com/*' => Http::response(null, 500)]);

    $movie = Movie::factory()->create([
        'external_ids' => ['imdb_id' => 'tt1111111'],
        'trailer_url' => null,
        'imdb_trailer_url' => null,
    ]);

    $this->artisan('trailers:download')->assertExitCode(0);

    expect($movie->fresh()->imdb_trailer_url)->toBe('');
});

it('descarta o vídeo se ele vier menor que o mínimo de 5MB', function () {
    Http::fake([
        'imdb.iamidiotareyoutoo.com/*' => Http::response(fakeVideoBody(1), 200, ['Content-Type' => 'video/mp4']),
    ]);

    $movie = Movie::factory()->create([
        'external_ids' => ['imdb_id' => 'tt2222222'],
        'trailer_url' => null,
        'imdb_trailer_url' => null,
    ]);

    $this->artisan('trailers:download')->assertExitCode(0);

    expect($movie->fresh()->imdb_trailer_url)->toBe('');
});

it('respeita o --limit', function () {
    Movie::factory()->count(3)->create([
        'external_ids' => ['imdb_id' => null],
        'trailer_url' => null,
        'imdb_trailer_url' => null,
    ]);

    $this->artisan('trailers:download --limit=1')
        ->expectsOutputToContain('Encontrados 1 filmes para processar.')
        ->assertExitCode(0);
});
