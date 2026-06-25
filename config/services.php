<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Flutterwave (primary payment provider)
    |--------------------------------------------------------------------------
    */
    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        // The "secret hash" you set in your Flutterwave dashboard webhook settings —
        // NOT the same as the secret key. Compared against the verif-hash header.
        'webhook_secret_hash' => env('FLUTTERWAVE_WEBHOOK_SECRET_HASH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paystack (fallback payment provider)
    |--------------------------------------------------------------------------
    */
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Termii (SMS)
    |--------------------------------------------------------------------------
    */
    'termii' => [
        'key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'AfricStay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brevo (transactional email)
    |--------------------------------------------------------------------------
    */
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'from_email' => env('BREVO_FROM_EMAIL', 'no-reply@africstayhms.com'),
        'from_name' => env('BREVO_FROM_NAME', 'AfricStay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary (room photos/videos, hotel logos)
    |--------------------------------------------------------------------------
    | We use Cloudinary's unsigned upload widget client-side (the browser
    | uploads directly to Cloudinary), then we just store the returned
    | secure_url + public_id. No server-side Cloudinary SDK call needed for
    | uploads — only for deletions, which use the Admin API below.
    */
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', 'africstay_unsigned'),
    ],

];
