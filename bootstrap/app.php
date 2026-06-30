<?php

use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\ImpersonationReadOnly;
use App\Http\Middleware\RedirectIfHotelAuthenticated;
use App\Http\Middleware\RedirectIfPlatformAuthenticated;
use App\Http\Middleware\EnsureLocationTierAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| NOTE FOR MERGING INTO AN EXISTING PROJECT
|--------------------------------------------------------------------------
| If you already have a bootstrap/app.php, don't overwrite it — just copy
| the ->alias([...]) block and the ->validateCsrfTokens(except: [...]) line
| into your existing ->withMiddleware() closure.
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'redirect.if.hotel.authenticated' => RedirectIfHotelAuthenticated::class,
            'redirect.if.platform.authenticated' => RedirectIfPlatformAuthenticated::class,
            'onboarding.complete' => EnsureOnboardingComplete::class,
            'subscription.active' => EnsureSubscriptionActive::class,
            'impersonation.readonly' => ImpersonationReadOnly::class,
            'location.tier' => EnsureLocationTierAccess::class,
        ]);

        // Flutterwave/Paystack POST here without a CSRF token — verified by
        // provider signature inside the controllers instead. See
        // App\Http\Controllers\Webhooks\*.
        $middleware->validateCsrfTokens(except: [
            'webhooks/flutterwave',
            'webhooks/paystack',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
