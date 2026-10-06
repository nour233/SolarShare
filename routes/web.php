<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ReviewController;

Route::get('/', [\App\Http\Controllers\EquipmentController::class, 'home'])->name('home');
Route::get('/equipments', [\App\Http\Controllers\EquipmentController::class, 'index'])->name('equipments.index');
Route::get('/equipments/{equipment}', [\App\Http\Controllers\EquipmentController::class, 'show'])->name('equipments.show');
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

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
    Route::get('/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->middleware('throttle:5,1')->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/reclamations', [ReclamationController::class, 'index'])->name('reclamations.index');
    Route::get('/reclamations/create', [ReclamationController::class, 'create'])->name('reclamations.create');
    Route::post('/reclamations', [ReclamationController::class, 'store'])->middleware('throttle:10,1')->name('reclamations.store');
    Route::get('/reclamations/{reclamation}', [ReclamationController::class, 'show'])->name('reclamations.show');
});

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

// ── Front-office rentals (auth required) ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/equipments/{equipment}/rent', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/equipments/{equipment}/rent', [RentalController::class, 'store'])->middleware('throttle:10,1')->name('rentals.store');
    Route::get('/rentals/{rental}', [RentalController::class, 'show'])->name('rentals.show');
    Route::patch('/rentals/{rental}/cancel', [RentalController::class, 'cancel'])->name('rentals.cancel');
});

// ── Back-office admin ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)
        ->except('show')->names('admin.categories');

    Route::resource('equipments', \App\Http\Controllers\Admin\EquipmentController::class)
        ->except('show')->names('admin.equipments')->parameters(['equipments' => 'equipment']);

    // Rentals resource (full CRUD)
    Route::resource('rentals', \App\Http\Controllers\Admin\RentalController::class)
        ->names('admin.rentals');

    // Extra rental actions
    Route::patch('rentals/{rental}/status', [\App\Http\Controllers\Admin\RentalController::class, 'updateStatus'])
        ->name('admin.rentals.status');

    // Payments nested under a rental
    Route::post('rentals/{rental}/payments', [\App\Http\Controllers\Admin\RentalController::class, 'storePayment'])
        ->name('admin.rentals.payments.store');
    Route::patch('rentals/{rental}/payments/{payment}', [\App\Http\Controllers\Admin\RentalController::class, 'updatePayment'])
        ->name('admin.rentals.payments.update');
    Route::delete('rentals/{rental}/payments/{payment}', [\App\Http\Controllers\Admin\RentalController::class, 'destroyPayment'])
        ->name('admin.rentals.payments.destroy');

    Route::resource('maintenances', \App\Http\Controllers\Admin\MaintenanceController::class)->except('show')->names('admin.maintenances');
    Route::resource('incidents', \App\Http\Controllers\Admin\IncidentController::class)->names('admin.incidents');
    Route::patch('/incidents/{incident}/status', [\App\Http\Controllers\Admin\IncidentController::class, 'updateStatus'])->name('admin.incidents.status');
    Route::get('/reclamations', [\App\Http\Controllers\Admin\ReclamationController::class, 'index'])->name('admin.reclamations.index');
    Route::get('/reclamations/{reclamation}', [\App\Http\Controllers\Admin\ReclamationController::class, 'show'])->name('admin.reclamations.show');
    Route::patch('/reclamations/{reclamation}/status', [\App\Http\Controllers\Admin\ReclamationController::class, 'updateStatus'])->name('admin.reclamations.status');
    Route::delete('/reclamations/{reclamation}', [\App\Http\Controllers\Admin\ReclamationController::class, 'destroy'])->name('admin.reclamations.destroy');
    Route::get('/reputation', [\App\Http\Controllers\Admin\ReputationController::class, 'index'])->name('admin.reputation.index');
    Route::get('/reputation/clients', [\App\Http\Controllers\Admin\ReputationController::class, 'clients'])->name('admin.reputation.clients');
    Route::get('/reputation/clients/{user}', [\App\Http\Controllers\Admin\ReputationController::class, 'client'])->name('admin.reputation.client');
    Route::delete('/reputation/reviews/{review}', [\App\Http\Controllers\Admin\ReputationController::class, 'destroyReview'])->name('admin.reputation.reviews.destroy');

    Route::get('/dashboard', function () {
        return view('back.dashboard', [
            'equipmentCount' => \App\Models\Equipment::count(),
            'categoryCount'  => \App\Models\Category::count(),
            'rentalCount'    => \App\Models\Rental::count(),
            'pendingCount'   => \App\Models\Rental::where('status', 'pending')->count(),
            'equipments'     => \App\Models\Equipment::with(['category', 'owner'])->latest()->paginate(10),
        ]);
    })->name('admin.dashboard');
});
