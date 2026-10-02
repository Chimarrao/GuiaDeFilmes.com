<?php

use Illuminate\Support\Facades\Cache;

it('devolve 500 quando o cache de países não foi gerado', function () {
    $response = $this->getJson('/api/countries');

    $response->assertStatus(500);
    $response->assertJsonStructure(['error']);
});

it('devolve a lista de países do cache', function () {
    $countries = [
        ['code' => 'BR', 'name' => 'Brasil', 'count' => 42],
        ['code' => 'US', 'name' => 'Estados Unidos', 'count' => 100],
    ];

    Cache::put('countries_with_counts_v2', $countries, 3600);

    $response = $this->getJson('/api/countries');

    $response->assertOk();
    $response->assertExactJson($countries);
});
