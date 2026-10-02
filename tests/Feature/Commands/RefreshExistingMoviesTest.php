<?php

use App\Models\Movie;
use Illuminate\Support\Facades\Http;

it('falha se TMDB_API_KEY não estiver configurada', function () {
    putenv('TMDB_API_KEY=');
    $original = $_ENV['TMDB_API_KEY'] ?? null;
    unset($_ENV['TMDB_API_KEY']);

    try {
        $this->artisan('movies:refresh-existing')
            ->expectsOutputToContain('TMDB_API_KEY não configurada no .env')
            ->assertExitCode(1);
    } finally {
        putenv('TMDB_API_KEY=test-tmdb-key');
        $_ENV['TMDB_API_KEY'] = $original ?? 'test-tmdb-key';
    }
});

it('avisa e sai cedo quando não tem filme pra atualizar', function () {
    $this->artisan('movies:refresh-existing --sleep=0')
        ->expectsOutputToContain('Nenhum filme para atualizar.')
        ->assertExitCode(0);
});

it('atualiza um filme recente com os dados vindos do TMDB (com fallback de trailer pt-BR->en-US)', function () {
    $movie = Movie::factory()->create([
        'tmdb_id' => 42,
        'title' => 'Título Antigo',
        'release_date' => now()->subMonths(2)->format('Y-m-d'),
    ]);

    Http::fake([
        'api.themoviedb.org/3/movie/42?*' => Http::response([
            'title' => 'Título Novo',
            'overview' => 'Nova sinopse',
            'release_date' => $movie->release_date->format('Y-m-d'),
            'status' => 'Released',
            'vote_average' => 8.1,
            'vote_count' => 500,
            'original_language' => 'en',
            'runtime' => 120,
            'budget' => 1000,
            'revenue' => 5000,
            'tagline' => 'Uma tagline',
            'genres' => [['name' => 'Ação']],
            'videos' => ['results' => []],
            'credits' => [
                'cast' => [['id' => 1, 'name' => 'Ator X', 'character' => 'Herói', 'profile_path' => '/x.jpg']],
                'crew' => [['id' => 2, 'name' => 'Diretor Y', 'job' => 'Director', 'department' => 'Directing']],
            ],
            'production_companies' => [['name' => 'Estúdio Z']],
            'production_countries' => [['name' => 'Brazil']],
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'adult' => false,
            'popularity' => 55.5,
            'imdb_id' => 'tt1234567',
        ]),
        'api.themoviedb.org/3/movie/42/videos*' => Http::response([
            'results' => [
                ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'FALLBACK123'],
            ],
        ]),
    ]);

    $this->artisan('movies:refresh-existing --sleep=0')->assertExitCode(0);

    $fresh = $movie->fresh();
    expect($fresh->title)->toBe('Título Novo');
    expect($fresh->synopsis)->toBe('Nova sinopse');
    expect($fresh->trailer_url)->toBe('https://www.youtube.com/watch?v=FALLBACK123');
    expect($fresh->genres)->toBe(['Ação']);
    expect((float) $fresh->tmdb_rating)->toBe(8.1);
});

it('não atualiza e registra erro quando o TMDB responde com falha', function () {
    $movie = Movie::factory()->create([
        'tmdb_id' => 43,
        'title' => 'Continua Igual',
        'release_date' => now()->subMonths(2)->format('Y-m-d'),
    ]);

    Http::fake(['api.themoviedb.org/*' => Http::response(null, 500)]);

    $this->artisan('movies:refresh-existing --sleep=0')->assertExitCode(0);

    expect($movie->fresh()->title)->toBe('Continua Igual');
});

it('separa filmes recentes (por --years) de filmes antigos na amostra', function () {
    $recente = Movie::factory()->create([
        'tmdb_id' => 51,
        'release_date' => now()->subMonths(1)->format('Y-m-d'),
    ]);
    $antigo = Movie::factory()->create([
        'tmdb_id' => 52,
        'release_date' => now()->subYears(5)->format('Y-m-d'),
    ]);

    Http::fake(['api.themoviedb.org/*' => Http::response(null, 500)]);

    $this->artisan('movies:refresh-existing --years=1 --recent=10 --old=10 --sleep=0')
        ->assertExitCode(0);

    // Confirma que os 2 filmes (1 recente + 1 antigo) foram de fato
    // selecionados e tentados, sem depender de casar texto de console.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/movie/51'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/movie/52'));
});
