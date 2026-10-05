<?php

use Illuminate\Support\Facades\Route;
Route::view('/', 'front.home')->name('home');
use App\Http\Controllers\Auth\RegisterController;

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);