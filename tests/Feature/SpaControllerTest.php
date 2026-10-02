<?php

use App\Models\Movie;

it('serve o build do frontend pra home com meta tags padrão', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('<title>Guia de Filmes - Descubra os Melhores Filmes e Onde Assistir</title>', false);
    $response->assertSee('<link rel="canonical" href="https://guiadefilmes.com/" />', false);

    File::delete($indexPath);
});

it('preenche title/canonical/conteúdo certos pra página de um filme existente', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $movie = Movie::factory()->create([
        'title' => 'Filme Teste Spa',
        'slug' => 'filme-teste-spa',
        'synopsis' => 'Uma sinopse qualquer para o teste.',
        'release_date' => '2024-05-10',
        'tmdb_vote_count' => 1000,
        'tmdb_rating' => 8.2,
        'genres' => ['Ação', 'Aventura'],
    ]);

    $response = $this->get('/filme/' . $movie->slug);

    $response->assertOk();
    $response->assertSee('Filme Teste Spa (2024) - Data de Lançamento, Elenco e Trailer | Guia de Filmes', false);
    $response->assertSee('<link rel="canonical" href="https://guiadefilmes.com/filme/filme-teste-spa" />', false);
    $response->assertSee('<div id="app"><h1>Filme Teste Spa (2024)</h1>', false);
    $response->assertSee('application/ld+json', false);

    File::delete($indexPath);
});

it('devolve 404 de verdade pra um slug de filme que não existe', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/filme/nao-existe-de-jeito-nenhum');

    $response->assertNotFound();

    File::delete($indexPath);
});

it('preenche title certo pra página de gênero', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/explorar/genero/acao');

    $response->assertOk();
    $response->assertSee('Ação - Explorar - Guia de Filmes', false);

    File::delete($indexPath);
});

it('preenche title certo pra página de década', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/explorar/decada/2020s');

    $response->assertOk();
    $response->assertSee('Anos 2020 - Explorar - Guia de Filmes', false);

    File::delete($indexPath);
});

it('preenche title certo pra página de país', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/explorar/pais/BR');

    $response->assertOk();
    $response->assertSee('Filmes Brasil - Guia de Filmes', false);

    File::delete($indexPath);
});

it('devolve 500 quando o build do frontend não existe', function () {
    File::delete(public_path('index.html'));

    $response = $this->get('/');

    $response->assertStatus(500);
});

it('preenche title certo pra cada página estática', function (string $path, string $expectedTitle) {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get($path);

    $response->assertOk();
    $response->assertSee($expectedTitle, false);

    File::delete($indexPath);
})->with([
    ['/estreias', 'Próximas Estreias - Guia de Filmes'],
    ['/em-cartaz', 'Filmes em Cartaz - Guia de Filmes'],
    ['/lancamentos', 'Lançados em Alta - Guia de Filmes'],
    ['/sobre', 'Sobre - Guia de Filmes'],
    ['/buscar', 'Buscar Filmes - Guia de Filmes'],
    ['/explorar', 'Explorar - Guia de Filmes'],
]);

it('usa o default da home (com canonical certo) pra rota não mapeada', function () {
    $indexPath = public_path('index.html');
    File::ensureDirectoryExists(public_path());
    File::put($indexPath, fixtureIndexHtml());

    $response = $this->get('/rota-que-nao-existe-no-vue-router');

    $response->assertOk();
    $response->assertSee('<link rel="canonical" href="https://guiadefilmes.com/rota-que-nao-existe-no-vue-router" />', false);

    File::delete($indexPath);
});

function fixtureIndexHtml(): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta name="description" content="Guia de Filmes - Seu guia completo de cinema!">
  <link rel="canonical" href="https://guiadefilmes.com/" />
  <meta property="og:url" content="https://guiadefilmes.com/">
  <meta property="og:title" content="Guia de Filmes - Seu Guia Completo de Cinema">
  <meta property="og:description" content="Explore o melhor do cinema">
  <meta property="og:type" content="website">
  <meta property="og:image" content="https://guiadefilmes.com/og-image.jpg">
  <meta property="twitter:url" content="https://guiadefilmes.com/">
  <meta property="twitter:title" content="Guia de Filmes - Seu Guia Completo de Cinema">
  <meta property="twitter:description" content="Explore o melhor do cinema">
  <meta property="twitter:image" content="https://guiadefilmes.com/og-image.jpg">
  <title>Guia de Filmes - Descubra os Melhores Filmes e Onde Assistir</title>
</head>
<body>
  <div id="app"></div>
  <script type="module" src="/src/main.js"></script>
</body>
</html>
HTML;
}
