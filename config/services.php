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
        /** WhatsApp ke muthowif setelah jamaah lunas (payment gateway). */
        'payment_notify_enabled' => env('FONNTE_PAYMENT_NOTIFY_ENABLED', true),
        /** WhatsApp ke customer setelah booking disetujui muthowif. */
        'customer_booking_approved_notify_enabled' => env('FONNTE_CUSTOMER_BOOKING_APPROVED_NOTIFY_ENABLED', true),
        /** WA ke jamaah: bukti transfer refund (setelah admin upload + tandai selesai). */
        'refund_transfer_proof_notify_enabled' => env('FONNTE_REFUND_TRANSFER_PROOF_NOTIFY_ENABLED', true),
        /** WA ke muthowif: bukti transfer withdraw (setelah admin tandai selesai). */
        'withdrawal_transfer_proof_notify_enabled' => env('FONNTE_WITHDRAWAL_TRANSFER_PROOF_NOTIFY_ENABLED', true),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        /** Env booleans are strings; cast so "false" is not treated as true in PHP. */
        'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        'core_payment_expire_minutes' => env('MIDTRANS_CORE_PAYMENT_EXPIRE_MINUTES', 60),
    ],

    'doku' => [
        'client_id' => trim((string) env('DOKU_CLIENT_ID', '')),
        'secret_key' => trim((string) env('DOKU_SECRET_KEY', '')),
        'is_production' => filter_var(env('DOKU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        'payment_due_minutes' => env('DOKU_PAYMENT_DUE_MINUTES', 60),
        'va_expire_minutes' => env('DOKU_VA_EXPIRE_MINUTES', 60),
        /** Path only; harus sama dengan route notifikasi (verifikasi signature). */
        'notification_path' => env('DOKU_NOTIFICATION_PATH', '/payments/doku/notification'),
        /** DOKU tidak punya GoPay di Checkout; default ke DANA. Sesuaikan di .env jika perlu. */
        'gopay_checkout_method' => env('DOKU_GOPAY_CHECKOUT_METHOD', 'EMONEY_DANA'),
    ],

];
