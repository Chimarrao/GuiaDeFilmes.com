<?php

use App\Enums\GenreSlug;

it('tem um label em português pra cada caso', function () {
    foreach (GenreSlug::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

it('all() devolve o mapa slug => nome pra todo mundo', function () {
    $all = GenreSlug::all();

    expect($all)->toHaveCount(count(GenreSlug::cases()));
    expect($all['acao'])->toBe('Ação');
});

it('tryFromSlug resolve um slug válido e devolve null pra um inválido', function () {
    expect(GenreSlug::tryFromSlug('acao'))->toBe(GenreSlug::ACAO);
    expect(GenreSlug::tryFromSlug('slug-que-nao-existe'))->toBeNull();
});

it('toGenreName converte slug conhecido e cai pro próprio slug se não existir', function () {
    expect(GenreSlug::toGenreName('acao'))->toBe('Ação');
    expect(GenreSlug::toGenreName('slug-desconhecido'))->toBe('slug-desconhecido');
});
