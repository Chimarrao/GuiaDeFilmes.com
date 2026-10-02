<?php

use App\Enums\DecadeRange;

it('tem label e range pra cada década', function () {
    foreach (DecadeRange::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->range())->toHaveCount(2);
    }
});

it('all() devolve o mapa slug => label pra toda década', function () {
    $all = DecadeRange::all();

    expect($all)->toHaveCount(count(DecadeRange::cases()));
    expect($all['2020s'])->toBe('Anos 2020');
});

it('tryFromValue resolve um slug direto', function () {
    expect(DecadeRange::tryFromValue('2020s'))->toBe(DecadeRange::DECADES_2020);
});

it('tryFromValue resolve um ano numérico pra década certa', function () {
    expect(DecadeRange::tryFromValue(2023))->toBe(DecadeRange::DECADES_2020);
    expect(DecadeRange::tryFromValue(2015))->toBe(DecadeRange::DECADES_2010);
    expect(DecadeRange::tryFromValue(2005))->toBe(DecadeRange::DECADES_2000);
    expect(DecadeRange::tryFromValue(1995))->toBe(DecadeRange::DECADES_1990);
    expect(DecadeRange::tryFromValue(1985))->toBe(DecadeRange::DECADES_1980);
    expect(DecadeRange::tryFromValue(1975))->toBe(DecadeRange::DECADES_1970);
    expect(DecadeRange::tryFromValue(1965))->toBe(DecadeRange::DECADES_1960);
    expect(DecadeRange::tryFromValue(1955))->toBe(DecadeRange::DECADES_1950);
    expect(DecadeRange::tryFromValue(1945))->toBe(DecadeRange::DECADES_1940);
    expect(DecadeRange::tryFromValue(1935))->toBe(DecadeRange::DECADES_1930);
    expect(DecadeRange::tryFromValue(1925))->toBe(DecadeRange::DECADES_1920);
    expect(DecadeRange::tryFromValue(1900))->toBe(DecadeRange::PRE_1920);
});
