<?php

use Illuminate\Http\Request;
use App\Http\Controllers\JustWatchController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MovieOrderingController;
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
Route::get('/movies/genre/{genre}', [MovieController::class, 'byGenre']);
Route::get('/movies/decade/{decade}', [MovieController::class, 'byDecade']);
Route::get('/movies/filter', [MovieController::class, 'filter']);
Route::get('/movie/{slug}', [MovieController::class, 'show']);
Route::get('/justwatch/search', [JustWatchController::class, 'search']);

// Movie Ordering routes
Route::get('/movie-ordering/all', [MovieOrderingController::class, 'getAllOrderings']);
Route::get('/movie-ordering/{type}', [MovieOrderingController::class, 'getOrdering']);
Route::post('/movie-ordering/{type}', [MovieOrderingController::class, 'updateOrdering']);