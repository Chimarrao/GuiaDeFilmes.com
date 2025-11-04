<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catch-all para Vue Router (excluindo /api)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
