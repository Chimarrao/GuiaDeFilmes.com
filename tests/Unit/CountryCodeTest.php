<?php

use App\Enums\CountryCode;

it('tem label, fullName, flag e toArray pra cada país', function () {
    foreach (CountryCode::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->fullName())->toBeString()->not->toBeEmpty();
        expect($case->getFlagUrl())->toContain('flagcdn.com');
        expect($case->toArray())->toHaveKeys(['code', 'name', 'flag']);
    }
});

it('all() e allFullNames() cobrem todo mundo', function () {
    expect(CountryCode::all())->toHaveCount(count(CountryCode::cases()));
    expect(CountryCode::allFullNames())->toHaveCount(count(CountryCode::cases()));
    expect(CountryCode::all()['BR'])->toBe('Brasil');
    expect(CountryCode::allFullNames()['BR'])->toBe('Brazil');
});

it('tryFromCode aceita minúsculo e devolve null pra código inválido', function () {
    expect(CountryCode::tryFromCode('br'))->toBe(CountryCode::BRAZIL);
    expect(CountryCode::tryFromCode('BR'))->toBe(CountryCode::BRAZIL);
    expect(CountryCode::tryFromCode('ZZ'))->toBeNull();
});

it('findByEnglishName resolve pelo nome oficial', function () {
    expect(CountryCode::findByEnglishName('Brazil'))->toBe(CountryCode::BRAZIL);
    expect(CountryCode::findByEnglishName('brazil'))->toBe(CountryCode::BRAZIL);
});

it('findByEnglishName resolve aliases/variações conhecidas', function () {
    expect(CountryCode::findByEnglishName('United States of America'))->toBe(CountryCode::USA);
    expect(CountryCode::findByEnglishName('UK'))->toBe(CountryCode::UNITED_KINGDOM);
    expect(CountryCode::findByEnglishName('Holland'))->toBe(CountryCode::NETHERLANDS);
    expect(CountryCode::findByEnglishName('North Korea'))->toBe(CountryCode::NORTH_KOREA);
});

it('findByEnglishName devolve null pra nome desconhecido', function () {
    expect(CountryCode::findByEnglishName('Narnia'))->toBeNull();
});
