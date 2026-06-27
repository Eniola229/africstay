<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Hotel\ApiTokenController;
use App\Http\Controllers\Hotel\BookingController;
use App\Http\Controllers\Hotel\DashboardController;
use App\Http\Controllers\Hotel\GuestController;
use App\Http\Controllers\Hotel\HousekeepingController;
use App\Http\Controllers\Hotel\LocationController;
use App\Http\Controllers\Hotel\ReportController;
use App\Http\Controllers\Hotel\RoomController;
use App\Http\Controllers\Hotel\RoomServiceController;
use App\Http\Controllers\Hotel\SettingsController;
use App\Http\Controllers\Hotel\StaffController;
use App\Http\Controllers\Hotel\StaffInviteController;
use App\Http\Controllers\Hotel\SubscriptionController;
use App\Http\Controllers\Hotel\WalletController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Platform\Auth\LoginController as PlatformLoginController;
use App\Http\Controllers\Platform\AdminManagementController;
use App\Http\Controllers\Platform\EnterpriseInquiryController as PlatformEnterpriseInquiryController;
use App\Http\Controllers\Platform\HotelManagementController;
use App\Http\Controllers\Platform\RevenueReportController;
use App\Http\Controllers\Public\EnterpriseInquiryController;
use App\Http\Controllers\Public\HotelPublicController;
use App\Http\Controllers\Webhooks\FlutterwaveWebhookController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');
Route::view('/terms-of-service', 'legal.terms-of-service')->name('legal.terms');
Route::view('privacy-policy', 'legal.privacy-policy')->name('legal.privacy');

/*
|--------------------------------------------------------------------------
| PUBLIC HOTEL BOOKING PAGE — no auth, no guard. Each hotel's public link:
| africstayhms.com/hotel/{slug}. Rate-limited since this accepts unauthenticated
| writes (guest self-booking).
|--------------------------------------------------------------------------
*/
Route::prefix('hotel/{slug}')->name('public.')->group(function () {
    Route::get('/', [HotelPublicController::class, 'show'])->name('hotel.show');
    Route::get('/availability', [HotelPublicController::class, 'checkAvailability'])->name('booking.availability');
    Route::post('/book', [HotelPublicController::class, 'store'])->name('booking.store')->middleware('throttle:10,1');
    Route::get('/booking/callback', [HotelPublicController::class, 'callback'])->name('booking.callback');
    Route::get('/booking/{reference}', [HotelPublicController::class, 'confirmation'])->name('booking.confirmation');
    Route::get('/booking/{reference}/check-status', [HotelPublicController::class, 'checkPaymentStatus'])
    ->name('booking.check-status');
    
});

Route::post('/enterprise-inquiry', [EnterpriseInquiryController::class, 'store'])
    ->name('public.enterprise-inquiry.store')
    ->middleware('throttle:5,1');

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
Route::middleware(['auth:web', 'subscription.active', 'onboarding.complete', 'impersonation.readonly'])->group(function () {
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
        Route::get('{booking}/check-payment', [BookingController::class, 'checkPayment'])->name('check-payment');
    });

    Route::prefix('wallet')->name('hotel.wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
        Route::post('/withdrawals', [WalletController::class, 'storeWithdrawal'])->name('withdrawals.store');
        Route::get('/banks',          [WalletController::class, 'listBanks'])->name('banks');
        Route::get('/verify-account', [WalletController::class, 'verifyAccount'])->name('verify-account');
    });

    Route::prefix('settings')->name('hotel.settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'show'])->name('index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile');
        Route::post('/online-booking', [SettingsController::class, 'updateOnlineBooking'])->name('online-booking');
        Route::post('/branding', [SettingsController::class, 'updateBranding'])->name('branding');

        Route::get('/api', [ApiTokenController::class, 'show'])->name('api.show');
        Route::post('/api/generate', [ApiTokenController::class, 'generate'])->name('api.generate');
        Route::delete('/api/{tokenId}', [ApiTokenController::class, 'revoke'])->name('api.revoke');
    });

    Route::prefix('housekeeping')->name('hotel.housekeeping.')->group(function () {
        Route::get('/', [HousekeepingController::class, 'index'])->name('index');
        Route::post('/{task}/checklist', [HousekeepingController::class, 'updateChecklist'])->name('checklist');
        Route::post('/{task}/cleaned', [HousekeepingController::class, 'markCleaned'])->name('cleaned');
        Route::post('/{task}/reassign', [HousekeepingController::class, 'reassign'])->name('reassign');
        Route::post('/{task}/verify', [HousekeepingController::class, 'verify'])->name('verify');
    });

    Route::prefix('room-service')->name('hotel.room-service.')->group(function () {
        Route::get('/items', [RoomServiceController::class, 'items'])->name('items');
        Route::post('/items', [RoomServiceController::class, 'storeItem'])->name('items.store');
        Route::post('/items/{item}/toggle', [RoomServiceController::class, 'toggleItem'])->name('items.toggle');
        Route::get('/orders', [RoomServiceController::class, 'orders'])->name('orders');
        Route::post('/bookings/{booking}/orders', [RoomServiceController::class, 'addOrder'])->name('orders.add');
        Route::post('/orders/{order}/status', [RoomServiceController::class, 'updateOrderStatus'])->name('orders.update');
    });

    Route::prefix('staff')->name('hotel.staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::post('/invite', [StaffController::class, 'invite'])->name('invite');
        Route::post('/{staff}/deactivate', [StaffController::class, 'deactivate'])->name('deactivate');
        Route::post('/{staff}/reactivate', [StaffController::class, 'reactivate'])->name('reactivate');
    });

    Route::prefix('locations')->name('hotel.locations.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/', [LocationController::class, 'store'])->name('store');
    });

    Route::prefix('reports')->name('hotel.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/arrivals-departures', [ReportController::class, 'arrivalsDepartures'])->name('arrivals-departures');
        Route::get('/occupied-rooms', [ReportController::class, 'occupiedRooms'])->name('occupied-rooms');
        Route::get('/outstanding-balances', [ReportController::class, 'outstandingBalances'])->name('outstanding-balances');
        Route::get('/housekeeping-status', [ReportController::class, 'housekeepingStatus'])->name('housekeeping-status');
        Route::get('/room-service-orders', [ReportController::class, 'roomServiceOrders'])->name('room-service-orders');
        Route::get('/revenue-breakdown', [ReportController::class, 'revenueBreakdown'])->name('revenue-breakdown');
        Route::get('/payments-by-method', [ReportController::class, 'paymentsByMethod'])->name('payments-by-method');
        Route::get('/transaction-fees', [ReportController::class, 'transactionFees'])->name('transaction-fees');
        Route::get('/wallet-history', [ReportController::class, 'walletHistory'])->name('wallet-history');
        Route::get('/withdrawal-history', [ReportController::class, 'withdrawalHistory'])->name('withdrawal-history');
        Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
        Route::get('/export/csv/{report}', [ReportController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/pdf/{report}', [ReportController::class, 'exportPdf'])->name('export.pdf');
    });
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
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('hotels')->name('hotels.')->group(function () {
            Route::get('/', [HotelManagementController::class, 'index'])->name('index');
            Route::get('/{hotel}', [HotelManagementController::class, 'show'])->name('show');
            Route::post('/{hotel}/toggle-active', [HotelManagementController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{hotel}/change-tier', [HotelManagementController::class, 'changeTier'])->name('change-tier');
            Route::post('/{hotel}/impersonate', [HotelManagementController::class, 'impersonate'])->name('impersonate');
            Route::post('/stop-impersonating', [HotelManagementController::class, 'stopImpersonating'])->name('stop-impersonating');
        });

        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::get('/', [PlatformEnterpriseInquiryController::class, 'index'])->name('index');
            Route::post('/{inquiry}/assign', [PlatformEnterpriseInquiryController::class, 'assign'])->name('assign');
            Route::post('/{inquiry}/status', [PlatformEnterpriseInquiryController::class, 'updateStatus'])->name('update-status');
        });

        Route::prefix('revenue')->name('revenue.')->group(function () {
            Route::get('/', [RevenueReportController::class, 'index'])->name('index');
            Route::get('/withdrawals', [RevenueReportController::class, 'withdrawals'])->name('withdrawals');
        });

        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [AdminManagementController::class, 'index'])->name('index');
            Route::post('/', [AdminManagementController::class, 'store'])->name('store');
            Route::post('/{admin}/change-role', [AdminManagementController::class, 'changeRole'])->name('change-role');
            Route::post('/{admin}/toggle-active', [AdminManagementController::class, 'toggleActive'])->name('toggle-active');
            Route::get('/activity-log', [AdminManagementController::class, 'activityLog'])->name('activity-log');
        });
    });
});
