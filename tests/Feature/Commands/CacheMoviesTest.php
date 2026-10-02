<?php

use App\Models\Movie;
use App\Models\MovieOrdering;
use Illuminate\Support\Facades\Cache;

it('gera todos os caches de listagem com troca atômica (fase TEMP_ removida no final)', function () {
    $upcoming = Movie::factory()->create([
        'status' => 'upcoming',
        'genres' => ['Ação'],
        'release_date' => '2024-06-01',
        'production_countries' => [['iso_3166_1' => 'BR', 'name' => 'Brazil']],
    ]);
    $inTheaters = Movie::factory()->create(['status' => 'in_theaters', 'release_date' => '2024-01-01']);
    $released = Movie::factory()->create([
        'status' => 'released',
        'release_date' => now()->format('Y-m-d'),
    ]);

    $this->artisan('cache:generate')->assertExitCode(0);

    expect(Cache::get('upcoming_ids_v1'))->toContain($upcoming->id);
    expect(Cache::get('upcoming_total_count'))->toBe(1);

    expect(Cache::get('in_theaters_ids_v1'))->toContain($inTheaters->id);
    expect(Cache::get('in_theaters_total_count'))->toBe(1);

    expect(Cache::get('released_ids_v1'))->toContain($released->id);

    expect(Cache::get('genre_acao_ids_v7'))->toContain($upcoming->id);
    expect(Cache::get('filter_genre_acao_ids_v1'))->toContain($upcoming->id);

    expect(Cache::get('decade_2020s_ids_v2'))->toContain($upcoming->id);

    expect(Cache::get('country_BR_ids_v2'))->toContain($upcoming->id);

    $countriesList = Cache::get('countries_with_counts_v2');
    expect($countriesList)->not->toBeNull();
    expect(collect($countriesList)->firstWhere('code', 'BR')['count'])->toBeGreaterThanOrEqual(1);

    // Fase de limpeza: nenhuma chave TEMP_ deve sobreviver ao comando.
    expect(Cache::has('TEMP_upcoming_ids_v1'))->toBeFalse();
});

it('respeita a ordenação customizada do MovieOrdering pros filmes upcoming', function () {
    $ordenado = Movie::factory()->create(['status' => 'upcoming', 'tmdb_id' => 111]);
    $automatico = Movie::factory()->create(['status' => 'upcoming', 'tmdb_id' => 222]);

    MovieOrdering::first()->update([
        'upcoming' => [['id_tmdb' => 111, 'title' => $ordenado->title]],
    ]);

    $this->artisan('cache:generate')->assertExitCode(0);

    $ids = Cache::get('upcoming_ids_v1');
    expect($ids[0])->toBe($ordenado->id);
    expect($ids)->toContain($automatico->id);
});

it('não inclui filmes adultos nos caches de listagem', function () {
    $adulto = Movie::factory()->create(['status' => 'upcoming', 'adult' => true]);

    $this->artisan('cache:generate')->assertExitCode(0);

    expect(Cache::get('upcoming_ids_v1'))->not->toContain($adulto->id);
});

it('respeita a ordenação customizada pra in_theaters e released também', function () {
    $ordenadoInTheaters = Movie::factory()->create(['status' => 'in_theaters', 'tmdb_id' => 9001]);
    Movie::factory()->create(['status' => 'in_theaters', 'tmdb_id' => 9002]);

    $ordenadoReleased = Movie::factory()->create(['status' => 'released', 'tmdb_id' => 9003]);
    Movie::factory()->create(['status' => 'released', 'tmdb_id' => 9004]);

    MovieOrdering::first()->update([
        'in_theaters' => [['id_tmdb' => 9001, 'title' => $ordenadoInTheaters->title]],
        'released' => [['id_tmdb' => 9003, 'title' => $ordenadoReleased->title]],
    ]);

    $this->artisan('cache:generate')->assertExitCode(0);

    expect(Cache::get('in_theaters_ids_v1')[0])->toBe($ordenadoInTheaters->id);
    expect(Cache::get('released_ids_v1')[0])->toBe($ordenadoReleased->id);
});

it('mapeia um país extinto na lista de países com contagem', function () {
    Movie::factory()->create([
        'production_countries' => [['iso_3166_1' => 'SU', 'name' => 'Soviet Union']],
    ]);

    $this->artisan('cache:generate')->assertExitCode(0);

    $countriesList = Cache::get('countries_with_counts_v2');
    $sovietEntry = collect($countriesList)->firstWhere('code', 'SU');

    expect($sovietEntry)->not->toBeNull();
    expect($sovietEntry['extinct'])->toBeTrue();
});
