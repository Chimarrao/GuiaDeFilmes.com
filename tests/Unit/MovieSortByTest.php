<?php

use App\Enums\MovieSortBy;

it('tem label, coluna e direção pra cada opção', function () {
    foreach (MovieSortBy::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->column())->toBeString()->not->toBeEmpty();
        expect($case->direction())->toBeIn(['asc', 'desc']);
    }
});

it('title ordena ascendente e o resto descendente', function () {
    expect(MovieSortBy::TITLE->direction())->toBe('asc');
    expect(MovieSortBy::POPULARITY->direction())->toBe('desc');
});

it('all() devolve o mapa value => label pra toda opção', function () {
    $all = MovieSortBy::all();

    expect($all)->toHaveCount(count(MovieSortBy::cases()));
    expect($all['popularity'])->toBe('Popularidade');
});

it('tryFromValue resolve um valor válido e devolve null pra um inválido', function () {
    expect(MovieSortBy::tryFromValue('popularity'))->toBe(MovieSortBy::POPULARITY);
    expect(MovieSortBy::tryFromValue('nao-existe'))->toBeNull();
});
