<?php

use App\Console\Commands\CheckSubscriptionExpiry;
use App\Console\Commands\ExpireStalePendingBookings;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Subscription expiry watcher
|--------------------------------------------------------------------------
| Runs once a day. Sends 7/3/1-day renewal reminders (email + SMS) and
| flips subscriptions to past_due/expired once their paid period ends.
| Make sure your server's actual cron runs `php artisan schedule:run` every
| minute (standard Laravel setup) for this to fire.
*/
Schedule::command(CheckSubscriptionExpiry::class)
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Stale online-booking sweep
|--------------------------------------------------------------------------
| Frees up rooms held by abandoned online checkouts (pending, never paid).
| Runs every 30 minutes — frequent enough that a room doesn't stay blocked
| for long, infrequent enough not to be wasteful.
*/
Schedule::command(ExpireStalePendingBookings::class)
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();
