<?php

use Illuminate\Support\Facades\Route;
Route::view('/', 'front.home')->name('home');
use App\Http\Controllers\Auth\RegisterController;

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
Route::get('/register/verify', [RegisterController::class, 'verification'])->name('register.verify');
Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:10,1');
Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:3,1')->name('register.resend');
