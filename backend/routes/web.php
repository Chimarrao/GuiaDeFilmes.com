<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return File::get(public_path('index.html'));
});

// Catch-all para Vue Router (excluindo /api)
Route::get('/{any}', function () {
    return File::get(public_path('index.html'));
})->where('any', '^(?!api).*$');
