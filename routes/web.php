<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/movie/{movie_id}', [HomeController::class, 'show'])->name('movie.show');
