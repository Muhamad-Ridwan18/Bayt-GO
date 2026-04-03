<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
        'otp_enabled' => env('FONNTE_OTP_ENABLED', true),
        'country_code' => env('FONNTE_COUNTRY_CODE', '62'),
        /** Notifikasi WhatsApp ke muthowif saat ada booking masuk (perlu FONNTE_TOKEN). */
        'booking_notify_enabled' => env('FONNTE_BOOKING_NOTIFY_ENABLED', true),
        /** WhatsApp ke muthowif setelah jamaah lunas (Xendit). */
        'payment_notify_enabled' => env('FONNTE_PAYMENT_NOTIFY_ENABLED', true),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        // Iris Merchant Key dipakai untuk verifikasi webhook Payout (disbursement).
        'iris_merchant_key' => env('MIDTRANS_IRIS_MERCHANT_KEY'),
    ],

    'xendit' => [
        'api_key' => env('XENDIT_API_KEY'),
        'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
        // Token webhook yang ada di Xendit Dashboard (header: x-callback-token)
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
    ],

];
