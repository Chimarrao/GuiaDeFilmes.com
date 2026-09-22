<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Rota fallback APENAS para rotas que NÃO começam com /api. Serve o SPA
// buildado com title/meta/canonical/conteúdo já preenchidos pro path pedido
// (ver SpaController) — o Vue troca os mesmos elementos de novo ao montar,
// pra manter a navegação client-side funcionando normalmente.
Route::get('/{any}', [SpaController::class, 'render'])->where('any', '^(?!api)(?!.*\.).*$');
