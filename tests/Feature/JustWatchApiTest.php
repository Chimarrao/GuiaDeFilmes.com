<?php

// Nota: o endpoint chama um script Python real (scripts/justwatch.py) que
// bate na API do JustWatch pela rede — não é algo apropriado pra rodar em
// teste automatizado (lento, instável, dependente de rede/venv). Testamos
// só as partes determinísticas: validação de entrada e o middleware de
// restrição de acesso, que respondem sem chegar a invocar o Python.

it('devolve 400 quando nenhum título é informado', function () {
    $response = $this->getJson('/api/justwatch/search');

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Nenhum título informado']);
});

it('bloqueia acesso de uma origem não autorizada', function () {
    $response = $this->getJson('/api/justwatch/search?query=Matrix', [
        'Origin' => 'https://site-qualquer.com',
    ]);

    $response->assertStatus(403);
});

it('permite acesso vindo do domínio autorizado via Origin', function () {
    $response = $this->getJson('/api/justwatch/search', [
        'Origin' => 'https://guiadefilmes.com',
    ]);

    $response->assertStatus(400);
});

it('permite acesso vindo do domínio autorizado via Referer (sem Origin)', function () {
    $response = $this->getJson('/api/justwatch/search', [
        'Referer' => 'https://guiadefilmes.com/filme/matrix',
    ]);

    $response->assertStatus(400);
});

it('bloqueia Referer de origem não autorizada', function () {
    $response = $this->getJson('/api/justwatch/search', [
        'Referer' => 'https://site-qualquer.com/pagina',
    ]);

    $response->assertStatus(403);
});
