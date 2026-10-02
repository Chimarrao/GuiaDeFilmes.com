<?php

use App\Models\Movie;

it('gera o índice e todos os sitemaps (estático, gênero, década, país, filmes)', function () {
    $movie = Movie::factory()->create(['slug' => 'meu-filme-no-sitemap']);

    $this->artisan('sitemap:generate')->assertExitCode(0);

    $expectedFiles = [
        'sitemap.xml',
        'sitemap-static.xml',
        'sitemap-genres.xml',
        'sitemap-decades.xml',
        'sitemap-countries.xml',
        'sitemap-movies-1.xml',
    ];

    foreach ($expectedFiles as $file) {
        expect(File::exists(public_path($file)))->toBeTrue("esperava {$file}");
    }

    $index = File::get(public_path('sitemap.xml'));
    expect($index)->toContain('sitemap-movies-1.xml');
    expect($index)->toContain('<sitemapindex');

    $moviesSitemap = File::get(public_path('sitemap-movies-1.xml'));
    expect($moviesSitemap)->toContain('/filme/meu-filme-no-sitemap');

    $genresSitemap = File::get(public_path('sitemap-genres.xml'));
    expect($genresSitemap)->toContain('/explorar/genero/acao');

    $decadesSitemap = File::get(public_path('sitemap-decades.xml'));
    expect($decadesSitemap)->toContain('/explorar/decada/2020s');

    $countriesSitemap = File::get(public_path('sitemap-countries.xml'));
    expect($countriesSitemap)->toContain('/explorar/pais/BR');

    $staticSitemap = File::get(public_path('sitemap-static.xml'));
    expect($staticSitemap)->toContain('https://guiadefilmes.com/estreias');

    foreach ($expectedFiles as $file) {
        File::delete(public_path($file));
    }
});
