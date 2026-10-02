<?php

use App\Models\MovieOrdering;

// A migration que cria a tabela "movie_orderings" já insere uma linha
// inicial com arrays vazios (registro único) — por isso, depois de migrar,
// sempre existe uma MovieOrdering, mesmo sem nenhum dado configurado ainda.

it('getOrdering rejeita tipo inválido', function () {
    $response = $this->getJson('/api/movie-ordering/invalido');

    $response->assertStatus(400);
});

it('getOrdering devolve ordering vazio quando a linha seed ainda não tem dados', function () {
    $response = $this->getJson('/api/movie-ordering/upcoming');

    $response->assertOk();
    $response->assertExactJson(['type' => 'upcoming', 'ordering' => []]);
});

it('getOrdering devolve 404 se por algum motivo não existir nenhuma linha', function () {
    MovieOrdering::query()->delete();

    $response = $this->getJson('/api/movie-ordering/upcoming');

    $response->assertNotFound();
});

it('getOrdering devolve a ordenação salva pro tipo pedido', function () {
    MovieOrdering::first()->update([
        'upcoming' => [['id_tmdb' => 123, 'title' => 'Filme X']],
    ]);

    $response = $this->getJson('/api/movie-ordering/upcoming');

    $response->assertOk();
    $response->assertJsonPath('type', 'upcoming');
    $response->assertJsonPath('ordering.0.id_tmdb', 123);
});

it('getAllOrderings devolve os 3 tipos (vazios pela linha seed)', function () {
    $response = $this->getJson('/api/movie-ordering/all');

    $response->assertOk();
    $response->assertExactJson([
        'in_theaters' => [],
        'upcoming' => [],
        'released' => [],
    ]);
});

it('updateOrdering rejeita tipo inválido', function () {
    $response = $this->postJson('/api/movie-ordering/invalido', ['ordering' => []]);

    $response->assertStatus(400);
});

it('updateOrdering valida o formato de cada item', function () {
    $response = $this->postJson('/api/movie-ordering/upcoming', [
        'ordering' => [['id_tmdb' => 'não é número']],
    ]);

    $response->assertStatus(422);
});

it('updateOrdering salva a ordenação na linha existente', function () {
    $payload = [
        'ordering' => [
            ['id_tmdb' => 456, 'title' => 'Filme Novo'],
        ],
    ];

    $response = $this->postJson('/api/movie-ordering/in_theaters', $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'type' => 'in_theaters',
        'count' => 1,
    ]);

    $this->assertDatabaseCount('movie_orderings', 1);
    expect(MovieOrdering::first()->in_theaters)->toEqual($payload['ordering']);
});

it('updateOrdering cria a linha se ela não existir mais', function () {
    MovieOrdering::query()->delete();

    $payload = ['ordering' => [['id_tmdb' => 789, 'title' => 'Recriado']]];

    $this->postJson('/api/movie-ordering/released', $payload)->assertOk();

    $this->assertDatabaseCount('movie_orderings', 1);
    expect(MovieOrdering::first()->released)->toEqual($payload['ordering']);
});

it('updateOrdering substitui só o tipo alvo, sem afetar os outros', function () {
    MovieOrdering::first()->update([
        'in_theaters' => [['id_tmdb' => 1, 'title' => 'A']],
        'upcoming' => [['id_tmdb' => 2, 'title' => 'B']],
    ]);

    $this->postJson('/api/movie-ordering/upcoming', [
        'ordering' => [['id_tmdb' => 999, 'title' => 'C']],
    ])->assertOk();

    $ordering = MovieOrdering::first();
    expect($ordering->upcoming)->toEqual([['id_tmdb' => 999, 'title' => 'C']]);
    expect($ordering->in_theaters)->toEqual([['id_tmdb' => 1, 'title' => 'A']]);
});

it('getAllOrderings devolve arrays vazios se por algum motivo não existir nenhuma linha', function () {
    MovieOrdering::query()->delete();

    $response = $this->getJson('/api/movie-ordering/all');

    $response->assertOk();
    $response->assertExactJson([
        'in_theaters' => [],
        'upcoming' => [],
        'released' => [],
    ]);
});
