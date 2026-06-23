<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Hotel\DashboardController;
use App\Http\Controllers\Hotel\StaffInviteController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Platform\Auth\LoginController as PlatformLoginController;
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
| URL prefix: none (root-level, e.g. africstayhms.com/login)
|--------------------------------------------------------------------------
*/
Route::middleware('redirect.if.hotel.authenticated')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 attempts / 1 min lockout per spec

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/password/forgot', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'send'])->name('password.email');

    Route::get('/password/otp', [ForgotPasswordController::class, 'showOtpForm'])->name('password.otp.show');
    Route::post('/password/otp', [ForgotPasswordController::class, 'verifyOtpAndReset'])->name('password.otp.verify');

    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset.show');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth:web')
    ->name('logout');

// Staff accept-invite (token-based, not behind auth)
Route::get('/staff/invite/{token}', [StaffInviteController::class, 'show'])->name('staff.invite.accept');
Route::post('/staff/invite/{token}', [StaffInviteController::class, 'accept'])->name('staff.invite.store');

/*
|--------------------------------------------------------------------------
| Onboarding wizard — owner only, guard "web"
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
| Hotel dashboard — guard "web", forced through onboarding first if owner
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'onboarding.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('hotel.dashboard');
    // Room management, bookings, payments, etc. attach here in Phases 1–4.
});

/*
|--------------------------------------------------------------------------
| PLATFORM ADMIN — guard "platform", table "platform_admins"
| URL prefix: /platform (e.g. africstayhms.com/platform/login)
| Entirely separate middleware group. Never mixed with the routes above.
|--------------------------------------------------------------------------
*/
Route::prefix('platform')->name('platform.')->group(function () {

    Route::middleware('redirect.if.platform.authenticated')->group(function () {
        Route::get('/login', [PlatformLoginController::class, 'show'])->name('login');
        Route::post('/login', [PlatformLoginController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    Route::post('/logout', [PlatformLoginController::class, 'logout'])
        ->middleware('auth:platform')
        ->name('logout');

    Route::middleware('auth:platform')->group(function () {
        Route::view('/dashboard', 'platform.dashboard')->name('dashboard');
        // Hotel management, enterprise inquiries, revenue reports, settings
        // attach here in Phase 5 — see spec section "Platform Admin Panel".
    });
});