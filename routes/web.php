<?php

use Illuminate\Support\Facades\Route;

// Rota fallback APENAS para rotas que NÃO começam com /api
Route::get('/{any}', function () {
    return File::get(public_path('index.html'));
})->where('any', '^(?!api)(?!.*\.).*$');
