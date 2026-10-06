<?php

use Illuminate\Support\Facades\Route;
Route::get('/', [\App\Http\Controllers\EquipmentController::class, 'home'])->name('home');
Route::get('/equipments', [\App\Http\Controllers\EquipmentController::class, 'index'])->name('equipments.index');
Route::get('/equipments/{equipment}', [\App\Http\Controllers\EquipmentController::class, 'show'])->name('equipments.show');
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

// Maintenance et incidents: front office (logged-in users).
Route::middleware('auth')->group(function () {
    Route::get('/equipments/{equipment}/incidents/create', [\App\Http\Controllers\IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/equipments/{equipment}/incidents', [\App\Http\Controllers\IncidentController::class, 'store'])->middleware('throttle:10,1')->name('incidents.store');
    Route::get('/incidents', [\App\Http\Controllers\IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/unread-count', [\App\Http\Controllers\IncidentController::class, 'unreadCount'])->name('incidents.unread');
    Route::get('/incidents/{incident}', [\App\Http\Controllers\IncidentController::class, 'show'])->whereNumber('incident')->name('incidents.show');
    // Shared by the reporter and the admins (access checked in the controller).
    Route::get('/incidents/{incident}/messages', [\App\Http\Controllers\IncidentMessageController::class, 'index'])->name('incidents.messages.index');
    Route::post('/incidents/{incident}/messages', [\App\Http\Controllers\IncidentMessageController::class, 'store'])->middleware('throttle:20,1')->name('incidents.messages.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except('show')->names('admin.categories');
    Route::resource('equipments', \App\Http\Controllers\Admin\EquipmentController::class)->except('show')->names('admin.equipments')->parameters(['equipments'=>'equipment']);
    Route::resource('maintenances', \App\Http\Controllers\Admin\MaintenanceController::class)->except('show')->names('admin.maintenances');
    Route::resource('incidents', \App\Http\Controllers\Admin\IncidentController::class)->names('admin.incidents');
    Route::patch('/incidents/{incident}/status', [\App\Http\Controllers\Admin\IncidentController::class, 'updateStatus'])->name('admin.incidents.status');
    Route::get('/dashboard', function () {
        return view('back.dashboard', [
            'equipmentCount' => \App\Models\Equipment::count(),
            'categoryCount' => \App\Models\Category::count(),
            'equipments' => \App\Models\Equipment::with(['category', 'owner'])->latest()->paginate(10),
        ]);
    })->name('admin.dashboard');
});
