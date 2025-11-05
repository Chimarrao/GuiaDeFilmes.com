<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    $indexPath = public_path('index.html');

    if (File::exists($indexPath)) {
        return File::get($indexPath);
    }

    abort(404, "index.html not found");
})->where('any', '.*');