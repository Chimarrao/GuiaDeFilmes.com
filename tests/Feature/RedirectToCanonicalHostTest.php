<?php

it('redireciona www.guiadefilmes.com pro domínio canônico', function () {
    $response = $this->get('http://www.guiadefilmes.com/filme/algum-filme');

    $response->assertRedirect('https://guiadefilmes.com/filme/algum-filme');
    $response->assertStatus(301);
});

it('não redireciona chamadas de /api mesmo vindo de www (evita quebrar AJAX)', function () {
    $response = $this->get('http://www.guiadefilmes.com/api/countries');

    expect($response->status())->not->toBe(301);
});

it('não redireciona o domínio canônico (sem www)', function () {
    $response = $this->get('http://guiadefilmes.com/api/countries');

    expect($response->status())->not->toBe(301);
});
