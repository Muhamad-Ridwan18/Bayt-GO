<?php

return [

    /*
    | Salin foto marketplace ke storage/app/public agar Apache/Nginx menyajikan
    | tanpa boot Laravel (penting saat dev pakai php artisan serve — single-thread).
    */
    'public_media_enabled' => env('MARKETPLACE_PUBLIC_MEDIA', true),

    /** Cache resolve slug profil publik (detik) — penting jika DB jauh. */
    'profile_resolve_cache_seconds' => (int) env('MARKETPLACE_PROFILE_RESOLVE_CACHE_SECONDS', 600),

    /** Cache data halaman profil /layanan/{slug} (detik). */
    'profile_show_cache_seconds' => (int) env('MARKETPLACE_PROFILE_SHOW_CACHE_SECONDS', 600),

    /** Cache ringkasan dashboard customer (detik). */
    'customer_dashboard_cache_seconds' => (int) env('MARKETPLACE_CUSTOMER_DASHBOARD_CACHE_SECONDS', 90),

    /** Cache hasil pencarian /layanan (detik). */
    'search_cache_seconds' => (int) env('MARKETPLACE_SEARCH_CACHE_SECONDS', 180),

    /** Maks. tanggal libur muthowif yang di-load di halaman profil. */
    'profile_blocked_dates_limit' => (int) env('MARKETPLACE_PROFILE_BLOCKED_LIMIT', 60),

    /** Maks. portfolio + gambar di halaman profil (preview galeri). */
    'profile_portfolio_preview_limit' => (int) env('MARKETPLACE_PROFILE_PORTFOLIO_PREVIEW', 3),

    /** Maks. gambar per album portfolio di preview profil. */
    'profile_portfolio_images_limit' => (int) env('MARKETPLACE_PROFILE_PORTFOLIO_IMAGES', 12),
];
