<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default guard
    |--------------------------------------------------------------------------
    | Hotel users are the default ("web"). Platform admins ALWAYS use the
    | "platform" guard explicitly — they never share this default.
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    | Two fully separate guards, separate providers, separate user tables.
    | "web" and "platform" never overlap — no shared session, no shared
    | middleware group, no shared login URL. Do not merge these.
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'platform' => [
            'driver' => 'session',
            'provider' => 'platform_admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class, // hotel owners + staff ONLY
        ],

        'platform_admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\PlatformAdmin::class, // AfricStay internal team ONLY
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting passwords
    |--------------------------------------------------------------------------
    | Separate password broker + token table per guard, so a platform admin
    | reset token can never be replayed against the hotel user table or
    | vice versa.
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'platform_admins' => [
            'provider' => 'platform_admins',
            'table' => 'platform_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
