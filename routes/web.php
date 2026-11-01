<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Auth\FaceLoginController;
use App\Http\Controllers\FaceEnrollmentController;

Route::post('/equipments/ai-search', \App\Http\Controllers\EquipmentSearchController::class)
    ->middleware('throttle:5,1')->name('equipments.ai-search');

Route::get(
    '/',
    [\App\Http\Controllers\EquipmentController::class, 'home']
)->name('home');

Route::get(
    '/equipments',
    [\App\Http\Controllers\EquipmentController::class, 'index']
)->name('equipments.index');

Route::get(
    '/equipments/{equipment}',
    [\App\Http\Controllers\EquipmentController::class, 'show']
)->name('equipments.show');

Route::get(
    '/reviews',
    [ReviewController::class, 'index']
)->name('reviews.index');


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'create'])->name('password.request');
    Route::get('/forgot-password/code', [\App\Http\Controllers\Auth\PasswordResetController::class, 'code'])->name('password.code');
    Route::post('/forgot-password/code', [\App\Http\Controllers\Auth\PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.code.verify');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

    Route::get(
        '/register',
        [RegisterController::class, 'create']
    )->name('register');

    Route::post(
        '/register',
        [RegisterController::class, 'store']
    )->middleware('throttle:5,1');

    Route::get(
        '/login',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [LoginController::class, 'store']
    )->middleware('throttle:5,1');
    Route::post('/login/face', [FaceLoginController::class, 'store'])->middleware('throttle:5,1')->name('login.face');
    Route::get('/login/face/password', [FaceLoginController::class, 'passwordForm'])->name('login.face.password');
    Route::post('/login/face/password', [FaceLoginController::class, 'verifyPassword'])->middleware('throttle:5,1')->name('login.face.password.verify');

    // Second step of the login when 2FA is on.
    Route::get('/two-factor-challenge', [LoginController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [LoginController::class, 'verifyChallenge'])->middleware('throttle:10,1')->name('two-factor.verify');
});


Route::get(
    '/register/verify',
    [RegisterController::class, 'verification']
)->name('register.verify');

Route::post(
    '/register/verify',
    [RegisterController::class, 'verify']
)->middleware('throttle:10,1');

Route::post(
    '/register/resend',
    [RegisterController::class, 'resend']
)
    ->middleware('throttle:3,1')
    ->name('register.resend');

Route::post(
    '/logout',
    [LoginController::class, 'destroy']
)
    ->middleware('auth')
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Profile and two-factor authentication
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->middleware('throttle:6,1')->name('profile.destroy');
    Route::post('/profile/face-login', [FaceEnrollmentController::class, 'store'])->middleware('throttle:6,1')->name('face-login.store');
    Route::delete('/profile/face-login', [FaceEnrollmentController::class, 'destroy'])->middleware('throttle:6,1')->name('face-login.destroy');

    Route::post('/profile/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->middleware('throttle:6,1')->name('two-factor.enable');
    Route::post('/profile/two-factor/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('two-factor.confirm');
    Route::post('/profile/two-factor/recovery-codes', [\App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:6,1')->name('two-factor.recovery-codes');
    Route::delete('/profile/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('two-factor.disable');
});


/*
|--------------------------------------------------------------------------
| Front Office - Reviews
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/reviews/create',
        [ReviewController::class, 'create']
    )->name('reviews.create');

    Route::post(
        '/reviews',
        [ReviewController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('reviews.store');

    Route::get(
        '/reviews/{review}/edit',
        [ReviewController::class, 'edit']
    )->name('reviews.edit');

    Route::put(
        '/reviews/{review}',
        [ReviewController::class, 'update']
    )
        ->middleware('throttle:5,1')
        ->name('reviews.update');

    Route::delete(
        '/reviews/{review}',
        [ReviewController::class, 'destroy']
    )->name('reviews.destroy');
});


/*
|--------------------------------------------------------------------------
| Front Office - Reclamations
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/reclamations',
        [ReclamationController::class, 'index']
    )->name('reclamations.index');

    Route::get(
        '/reclamations/create',
        [ReclamationController::class, 'create']
    )->name('reclamations.create');

    Route::post(
        '/reclamations',
        [ReclamationController::class, 'store']
    )
        ->middleware('throttle:10,1')
        ->name('reclamations.store');

    Route::get(
        '/reclamations/{reclamation}',
        [ReclamationController::class, 'show']
    )->name('reclamations.show');
});


/*
|--------------------------------------------------------------------------
| Front Office - Incidents
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/equipments/{equipment}/incidents/create',
        [\App\Http\Controllers\IncidentController::class, 'create']
    )->name('incidents.create');

    Route::post(
        '/equipments/{equipment}/incidents',
        [\App\Http\Controllers\IncidentController::class, 'store']
    )
        ->middleware('throttle:10,1')
        ->name('incidents.store');

    Route::get(
        '/incidents',
        [\App\Http\Controllers\IncidentController::class, 'index']
    )->name('incidents.index');

    Route::get(
        '/incidents/unread-count',
        [\App\Http\Controllers\IncidentController::class, 'unreadCount']
    )->name('incidents.unread');

    Route::get(
        '/incidents/{incident}',
        [\App\Http\Controllers\IncidentController::class, 'show']
    )
        ->whereNumber('incident')
        ->name('incidents.show');

    // Shared by the reporter and the admins.
    Route::get(
        '/incidents/{incident}/messages',
        [\App\Http\Controllers\IncidentMessageController::class, 'index']
    )->name('incidents.messages.index');

    Route::post(
        '/incidents/{incident}/messages',
        [\App\Http\Controllers\IncidentMessageController::class, 'store']
    )
        ->middleware('throttle:20,1')
        ->name('incidents.messages.store');
});


/*
|--------------------------------------------------------------------------
| Front Office - Rentals & Deliveries
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Rentals
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rentals',
        [RentalController::class, 'index']
    )->name('rentals.index');

    Route::get(
        '/equipments/{equipment}/rent',
        [RentalController::class, 'create']
    )->name('rentals.create');

    Route::post(
        '/equipments/{equipment}/rent',
        [RentalController::class, 'store']
    )
        ->middleware('throttle:10,1')
        ->name('rentals.store');

    Route::get(
        '/rentals/{rental}',
        [RentalController::class, 'show']
    )->name('rentals.show');

    Route::patch(
        '/rentals/{rental}/cancel',
        [RentalController::class, 'cancel']
    )->name('rentals.cancel');

    // Live pricing estimate (AJAX)
    Route::get(
        '/equipments/{equipment}/rent/price',
        [RentalController::class, 'priceEstimate']
    )->name('rentals.price-estimate');


    /*
    |--------------------------------------------------------------------------
    | Deliveries - Client
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rentals/{rental}/delivery/create',
        [DeliveryController::class, 'create']
    )->name('deliveries.create');

    Route::post(
        '/rentals/{rental}/delivery',
        [DeliveryController::class, 'store']
    )->name('deliveries.store');

    Route::get(
        '/deliveries/{delivery}/edit',
        [DeliveryController::class, 'edit']
    )->name('deliveries.edit');

    Route::put(
        '/deliveries/{delivery}',
        [DeliveryController::class, 'update']
    )->name('deliveries.update');

    Route::delete(
        '/deliveries/{delivery}',
        [DeliveryController::class, 'destroy']
    )->name('deliveries.destroy');
});


/*
|--------------------------------------------------------------------------
| Back Office - Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'categories',
            \App\Http\Controllers\Admin\CategoryController::class
        )
            ->except('show')
            ->names('admin.categories');


        /*
        |--------------------------------------------------------------------------
        | Equipments
        |--------------------------------------------------------------------------
        */

        Route::post('equipments/analyze-photo', \App\Http\Controllers\Admin\EquipmentPhotoController::class)
            ->middleware('throttle:5,1')->name('admin.equipments.analyze-photo');

        Route::resource(
            'equipments',
            \App\Http\Controllers\Admin\EquipmentController::class
        )
            ->except('show')
            ->names('admin.equipments')
            ->parameters([
                'equipments' => 'equipment',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Rentals
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'rentals',
            \App\Http\Controllers\Admin\RentalController::class
        )->names('admin.rentals');

        Route::patch(
            'rentals/{rental}/status',
            [\App\Http\Controllers\Admin\RentalController::class, 'updateStatus']
        )->name('admin.rentals.status');

        // Statistics dashboard
        Route::get(
            'rentals-statistics',
            [\App\Http\Controllers\Admin\RentalController::class, 'statistics']
        )->name('admin.rentals.statistics');

        // PDF contract download
        Route::get(
            'rentals/{rental}/contract',
            [\App\Http\Controllers\Admin\RentalController::class, 'contract']
        )->name('admin.rentals.contract');

        // Pricing rules
        Route::get(
            'pricing',
            [\App\Http\Controllers\Admin\PricingRuleController::class, 'index']
        )->name('admin.pricing.index');

        Route::put(
            'pricing',
            [\App\Http\Controllers\Admin\PricingRuleController::class, 'update']
        )->name('admin.pricing.update');


        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */

        Route::post(
            'rentals/{rental}/payments',
            [\App\Http\Controllers\Admin\RentalController::class, 'storePayment']
        )->name('admin.rentals.payments.store');

        Route::patch(
            'rentals/{rental}/payments/{payment}',
            [\App\Http\Controllers\Admin\RentalController::class, 'updatePayment']
        )->name('admin.rentals.payments.update');

        Route::delete(
            'rentals/{rental}/payments/{payment}',
            [\App\Http\Controllers\Admin\RentalController::class, 'destroyPayment']
        )->name('admin.rentals.payments.destroy');


        /*
        |--------------------------------------------------------------------------
        | Pickup Points - Zied
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'pickup-points',
            \App\Http\Controllers\Admin\PickupPointController::class
        )->names('admin.pickup-points');


        /*
        |--------------------------------------------------------------------------
        | Deliveries - Zied
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'deliveries',
            \App\Http\Controllers\Admin\DeliveryController::class
        )->names('admin.deliveries');


        /*
        |--------------------------------------------------------------------------
        | Maintenance
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/maintenances/model',
            \App\Http\Controllers\Admin\RepairModelController::class
        )->name('admin.repair-model');

        Route::resource(
            'maintenances',
            \App\Http\Controllers\Admin\MaintenanceController::class
        )
            ->except('show')
            ->names('admin.maintenances');


        /*
        |--------------------------------------------------------------------------
        | Incidents
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'incidents',
            \App\Http\Controllers\Admin\IncidentController::class
        )->names('admin.incidents');

        Route::patch(
            '/incidents/{incident}/status',
            [\App\Http\Controllers\Admin\IncidentController::class, 'updateStatus']
        )->name('admin.incidents.status');

        Route::post(
            '/incidents/{incident}/diagnosis',
            [\App\Http\Controllers\Admin\IncidentController::class, 'diagnose']
        )->name('admin.incidents.diagnose');

        Route::patch(
            '/incidents/{incident}/severity',
            [\App\Http\Controllers\Admin\IncidentController::class, 'applySeverity']
        )->name('admin.incidents.severity');


        /*
        |--------------------------------------------------------------------------
        | Reclamations
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/reclamations',
            [\App\Http\Controllers\Admin\ReclamationController::class, 'index']
        )->name('admin.reclamations.index');

        Route::get(
            '/reclamations/{reclamation}',
            [\App\Http\Controllers\Admin\ReclamationController::class, 'show']
        )->name('admin.reclamations.show');

        Route::patch(
            '/reclamations/{reclamation}/status',
            [\App\Http\Controllers\Admin\ReclamationController::class, 'updateStatus']
        )->name('admin.reclamations.status');

        Route::delete(
            '/reclamations/{reclamation}',
            [\App\Http\Controllers\Admin\ReclamationController::class, 'destroy']
        )->name('admin.reclamations.destroy');


        /*
        |--------------------------------------------------------------------------
        | Reputation
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/reputation',
            [\App\Http\Controllers\Admin\ReputationController::class, 'index']
        )->name('admin.reputation.index');

        Route::get(
            '/reputation/clients',
            [\App\Http\Controllers\Admin\ReputationController::class, 'clients']
        )->name('admin.reputation.clients');

        Route::get(
            '/reputation/clients/{user}',
            [\App\Http\Controllers\Admin\ReputationController::class, 'client']
        )->name('admin.reputation.client');

        Route::delete(
            '/reputation/reviews/{review}',
            [\App\Http\Controllers\Admin\ReputationController::class, 'destroyReview']
        )->name('admin.reputation.reviews.destroy');


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', function () {

            return view('back.dashboard', [
                'equipmentCount' => \App\Models\Equipment::count(),

                'categoryCount' => \App\Models\Category::count(),

                'rentalCount' => \App\Models\Rental::count(),

                'pendingCount' => \App\Models\Rental::where(
                    'status',
                    'pending'
                )->count(),

                'equipments' => \App\Models\Equipment::with([
                    'category',
                    'owner',
                ])
                    ->latest()
                    ->paginate(10),
            ]);

        })->name('admin.dashboard');

        // Admin notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');


        /*
        |--------------------------------------------------------------------------
        | Segmentation RFM
        |--------------------------------------------------------------------------
        */
        Route::get('/segmentation', [\App\Http\Controllers\Admin\SegmentationController::class, 'index'])->name('admin.segmentation.index');
        Route::post('/segmentation/run', [\App\Http\Controllers\Admin\SegmentationController::class, 'run'])->name('admin.segmentation.run');
        Route::post('/segmentation/notify', [\App\Http\Controllers\Admin\SegmentationController::class, 'notify'])->name('admin.segmentation.notify');
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications (authenticated - shared for client & admin)
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/{id}/read-ajax', [NotificationController::class, 'markAsReadAjax'])->name('notifications.read-ajax');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    /*
    |--------------------------------------------------------------------------
    | Payments (Stripe Checkout & Invoices)
    |--------------------------------------------------------------------------
    */
    Route::post('/rentals/{rental}/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('/rentals/{rental}/checkout/sandbox', [PaymentController::class, 'processSandbox'])->name('payments.sandbox.process');
    Route::get('/rentals/{rental}/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/rentals/{rental}/payments/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/rentals/{rental}/payments/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');
