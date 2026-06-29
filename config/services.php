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

    /*
    | ISO 3166-1 alpha-2: dipakai saat pengguna tidak menulis +kode negara (nomor format lokal/domestik).
    | Contoh ID → 0812… dikenali sebagai Indonesia; untuk nomor luar negeri lebih aman tulis lengkap seperti +6591234567.
    */
    'phone' => [
        'default_region' => env('PHONE_DEFAULT_REGION', 'ID'),
    ],

    /*
    | WhatsApp gateway (format Fonnte: form POST target/message/countryCode).
    | Toggle notifikasi: Admin → Pengaturan → Notifikasi WhatsApp (site_settings).
    | Env FONNTE_*_ENABLED hanya fallback awal jika belum disimpan di admin.
    */
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_API_URL', 'https://whatsapp.baytgo.id/send'),
        /** URL publik untuk lampiran WA (WSM harus bisa unduh). Kosong = APP_URL. */
        'media_public_url' => env('FONNTE_MEDIA_PUBLIC_URL'),
        'session_id' => env('FONNTE_SESSION_ID'),
        'otp_enabled' => env('FONNTE_OTP_ENABLED', true),
        'country_code' => env('FONNTE_COUNTRY_CODE', '62'),
        /** Notifikasi WhatsApp ke muthowif saat ada booking masuk (perlu FONNTE_TOKEN). */
        'booking_notify_enabled' => env('FONNTE_BOOKING_NOTIFY_ENABLED', true),
        /** WhatsApp ke muthowif setelah jamaah lunas (payment gateway). */
        'payment_notify_enabled' => env('FONNTE_PAYMENT_NOTIFY_ENABLED', true),
        /** WhatsApp ke customer setelah booking disetujui muthowif. */
        'customer_booking_approved_notify_enabled' => env('FONNTE_CUSTOMER_BOOKING_APPROVED_NOTIFY_ENABLED', true),
        /** WA ke jamaah: muthowif menolak karena jadwal penuh — maaf + cek rekomendasi jaringan. */
        'customer_booking_rejected_jadwal_full_notify_enabled' => env('FONNTE_CUSTOMER_BOOKING_REJECTED_JADWAL_FULL_NOTIFY_ENABLED', true),
        /** WA ke jamaah: bukti transfer refund (setelah admin upload + tandai selesai). */
        'refund_transfer_proof_notify_enabled' => env('FONNTE_REFUND_TRANSFER_PROOF_NOTIFY_ENABLED', true),
        /** WA ke muthowif: bukti transfer withdraw (setelah admin tandai selesai). */
        'withdrawal_transfer_proof_notify_enabled' => env('FONNTE_WITHDRAWAL_TRANSFER_PROOF_NOTIFY_ENABLED', true),
        /** WA ke jamaah: ada calon muthowif pengganti yang menerima tawaran insiden. */
        'emergency_candidate_notify_enabled' => env('FONNTE_EMERGENCY_CANDIDATE_NOTIFY_ENABLED', true),
        /** WA ke muthowif: hasil pemilihan pengganti insiden (terpilih / belum terpilih). */
        'emergency_selection_notify_enabled' => env('FONNTE_EMERGENCY_SELECTION_NOTIFY_ENABLED', true),
        /** WA ke muthowif: tawaran pengganti insiden (broadcast / undangan admin). */
        'emergency_offer_notify_enabled' => env('FONNTE_EMERGENCY_OFFER_NOTIFY_ENABLED', true),
        /** WA ke nomor admin saat jamaah melaporkan insiden darurat. */
        'emergency_admin_report_notify_enabled' => env('FONNTE_EMERGENCY_ADMIN_REPORT_NOTIFY_ENABLED', true),
        /** WA ke nomor admin saat ada pendaftaran muthowif baru. */
        'muthowif_registration_admin_notify_enabled' => env('FONNTE_MUTHOWIF_REGISTRATION_ADMIN_NOTIFY_ENABLED', true),
        /** WA ke nomor admin saat jamaah mengajukan refund. */
        'refund_admin_notify_enabled' => env('FONNTE_REFUND_ADMIN_NOTIFY_ENABLED', true),
        /** WA ke jamaah: update status laporan insiden (ditinjau / diverifikasi / ditolak). */
        'emergency_customer_report_notify_enabled' => env('FONNTE_EMERGENCY_CUSTOMER_REPORT_NOTIFY_ENABLED', true),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        /** Env booleans are strings; cast so "false" is not treated as true in PHP. */
        'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        'core_payment_expire_minutes' => env('MIDTRANS_CORE_PAYMENT_EXPIRE_MINUTES', 60),
    ],

    'booking' => [
        /** Driver halaman bayar web: `doku` (default) atau `moota`. */
        'payment_driver' => env('BOOKING_PAYMENT_DRIVER', 'doku'),
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

    /** Webhook mutasi bank: hanya IPv4 dalam daftar yang boleh mengakses POST /webhooks/moota. */
    'moota' => [
        'webhook_ips' => [
            '103.236.201.178',
            '212.38.74.36',
            '128.199.173.138',
            '108.165.253.123', // Moota «Cek URL» / sandbox (dev.baytgo.id log)
        ],
        /** Secret untuk verifikasi header Signature (HMAC-SHA256 atas raw POST body); samakan dengan Moota. */
        'signing_secret' => (string) env('MOOTA_WEBHOOK_SIGNING_SECRET', ''),
        /** Outbound API v2 (Create Transaction). */
        'api_base_url' => rtrim((string) env('MOOTA_API_BASE_URL', 'https://api.moota.co'), '/'),
        'api_email' => (string) env('MOOTA_API_EMAIL', ''),
        'api_password' => (string) env('MOOTA_API_PASSWORD', ''),
        /**
         * Satu atau lebih ID rekening Moota (`bank_account_id` di Create Transaction). Pisahkan dengan koma/spasi.
         * Bila lebih dari satu, halaman bayar web menampilkan satu opsi per rekening.
         * Tanpa pilihan eksplisit, perilaku lain memakai {@see bank_account_pick}.
         */
        'bank_account_ids' => array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,]+/', (string) env('MOOTA_BANK_ACCOUNT_ID', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        ))),
        /** `first` | `round_robin` | `user` (fallback bila tidak ada rekening terpilih; web multi-rek: pakai opsi per ID). */
        'bank_account_pick' => strtolower(trim((string) env('MOOTA_BANK_ACCOUNT_PICK', 'first'))),
        'payment_expire_minutes' => (int) env('MOOTA_PAYMENT_EXPIRE_MINUTES', 1440),
        'token_cache_minutes' => (int) env('MOOTA_ACCESS_TOKEN_CACHE_MINUTES', 55),
        /** Rentang filter kode unik untuk webhook mutasi (POST /api/v2/integration/webhook). */
        'webhook_unique_start' => (int) env('MOOTA_WEBHOOK_UNIQUE_START', 0),
        'webhook_unique_end' => (int) env('MOOTA_WEBHOOK_UNIQUE_END', 999),
    ],

];
