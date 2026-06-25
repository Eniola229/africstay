<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Hotel\BookingController;
use App\Http\Controllers\Hotel\DashboardController;
use App\Http\Controllers\Hotel\GuestController;
use App\Http\Controllers\Hotel\RoomController;
use App\Http\Controllers\Hotel\StaffInviteController;
use App\Http\Controllers\Hotel\SubscriptionController;
use App\Http\Controllers\Hotel\WalletController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Platform\Auth\LoginController as PlatformLoginController;
use App\Http\Controllers\Webhooks\FlutterwaveWebhookController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| HOTEL USER AUTH — guard "web", table "users"
|--------------------------------------------------------------------------
*/
Route::middleware('redirect.if.hotel.authenticated')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/password/forgot', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'send'])->name('password.email');

    Route::get('/password/otp', [ForgotPasswordController::class, 'showOtpForm'])->name('password.otp.show');
    Route::post('/password/otp', [ForgotPasswordController::class, 'verifyOtpAndReset'])->name('password.otp.verify');

    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset.show');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::get('/staff/invite/{token}', [StaffInviteController::class, 'show'])->name('staff.invite.accept');
Route::post('/staff/invite/{token}', [StaffInviteController::class, 'accept'])->name('staff.invite.store');

/*
|--------------------------------------------------------------------------
| Subscription billing — plan picker is reachable even before payment
| (that's the whole point), checkout starts a Flutterwave/Paystack session.
| NOT behind EnsureSubscriptionActive (that would be circular).
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])->prefix('subscription')->name('hotel.subscription.')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'showPlans'])->name('plans');
    Route::get('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout.start');
    Route::get('/callback', [SubscriptionController::class, 'callback'])->name('callback');
});

/*
|--------------------------------------------------------------------------
| Payment webhooks — public, no CSRF, verified by provider signature inside
| the controllers themselves. Make sure these two paths are added to the
| `except` array of VerifyCsrfToken (or excluded in bootstrap/app.php on
| Laravel 11) since providers POST here without a CSRF token.
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle'])->name('webhooks.flutterwave');
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

/*
|--------------------------------------------------------------------------
| Onboarding wizard — owner only. Steps 3/4 are additionally gated by
| EnsureSubscriptionActive (applied globally below) once payment is required.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'show'])->name('show');
    Route::post('/details', [OnboardingController::class, 'saveDetails'])->name('details');
    Route::post('/tier', [OnboardingController::class, 'saveTier'])->name('tier');
    Route::post('/rooms', [OnboardingController::class, 'saveRooms'])->name('rooms');
    Route::post('/staff', [OnboardingController::class, 'saveStaff'])->name('staff');
});

/*
|--------------------------------------------------------------------------
| Hotel dashboard + operational routes — guard "web", forced through
| onboarding AND an active subscription before anything else loads.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'subscription.active', 'onboarding.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('hotel.dashboard');

    Route::prefix('rooms')->name('hotel.rooms.')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('index');
        Route::get('/create', [RoomController::class, 'create'])->name('create');
        Route::post('/', [RoomController::class, 'store'])->name('store');
        Route::get('/{room}/edit', [RoomController::class, 'edit'])->name('edit');
        Route::put('/{room}', [RoomController::class, 'update'])->name('update');
        Route::post('/{room}/media', [RoomController::class, 'addMedia'])->name('media.store');
        Route::delete('/{room}/media/{mediaId}', [RoomController::class, 'removeMedia'])->name('media.destroy');
        Route::post('/{room}/maintenance', [RoomController::class, 'blockForMaintenance'])->name('maintenance');
    });

    Route::get('/guests/search', [GuestController::class, 'search'])->name('hotel.guests.search');

    Route::prefix('bookings')->name('hotel.bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/create', [BookingController::class, 'create'])->name('create');
        Route::get('/available-rooms', [BookingController::class, 'availableRooms'])->name('available-rooms');
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::post('/{booking}/check-in', [BookingController::class, 'checkIn'])->name('check-in');
        Route::post('/{booking}/check-out', [BookingController::class, 'checkOut'])->name('check-out');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('wallet')->name('hotel.wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
        Route::post('/withdrawals', [WalletController::class, 'storeWithdrawal'])->name('withdrawals.store');
    });

    // Housekeeping, room service, staff management, reports attach here in later phases.
});

/*
|--------------------------------------------------------------------------
| PLATFORM ADMIN — guard "platform", table "platform_admins"
|--------------------------------------------------------------------------
*/
Route::prefix('platform')->name('platform.')->group(function () {

    Route::middleware('redirect.if.platform.authenticated')->group(function () {
        Route::get('/login', [PlatformLoginController::class, 'show'])->name('login');
        Route::post('/login', [PlatformLoginController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::post('/logout', [PlatformLoginController::class, 'logout'])->middleware('auth:platform')->name('logout');

    Route::middleware('auth:platform')->group(function () {
        Route::view('/dashboard', 'platform.dashboard')->name('dashboard');
    });
});
