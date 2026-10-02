<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;

// Nenhuma rota da aplicação usa os middlewares 'auth'/'guest' (é uma API
// pública sem login) — boilerplate padrão do Laravel, nunca exercitado em
// produção. Não existe nem migration pra tabela "users" neste projeto, então
// o ramo "usuário autenticado" desses middlewares é literalmente inalcançável
// aqui — só o caminho "sem sessão" é testável de forma honesta.

it('Authenticate::redirectTo devolve null pra requisição que espera JSON', function () {
    $middleware = app(Authenticate::class);

    $reflection = new ReflectionMethod($middleware, 'redirectTo');
    $reflection->setAccessible(true);

    $request = Request::create('/api/qualquer', 'GET');
    $request->headers->set('Accept', 'application/json');

    expect($reflection->invoke($middleware, $request))->toBeNull();
});

it('RedirectIfAuthenticated deixa passar quando não há usuário logado', function () {
    $middleware = new RedirectIfAuthenticated();

    $response = $middleware->handle(Request::create('/'), fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});
