<?php

use Illuminate\Support\Facades\Route;
Route::view('/', 'front.home')->name('home');
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});
Route::get('/register/verify', [RegisterController::class, 'verification'])->name('register.verify');
Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:10,1');
Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:3,1')->name('register.resend');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::view('/dashboard', 'back.dashboard')->name('admin.dashboard');
});
