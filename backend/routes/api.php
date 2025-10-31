<?php

use Illuminate\Http\Request;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/upcoming', [MovieController::class, 'upcoming']);
Route::get('/movies/in-theaters', [MovieController::class, 'inTheaters']);
Route::get('/movies/released', [MovieController::class, 'released']);
Route::get('/movie/{slug}', [MovieController::class, 'show']);
Route::get('/movie/{slug}/reviews', [ReviewController::class, 'index']);