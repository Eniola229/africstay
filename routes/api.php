<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AfricStay API — Pro tier+ (spec: "API access" is a Pro-tier perk)
|--------------------------------------------------------------------------
| Token-gated via Sanctum (App\Http\Controllers\Hotel\ApiTokenController
| issues tokens from Settings > API, owner-only). Every endpoint scopes to
| $request->user()->hotel automatically — there's no hotel_id parameter to
| pass, so a token can never be used to read another hotel's data.
*/
Route::middleware('auth:sanctum')->prefix('v1')->name('api.')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
});
