<?php

return [
    /** Cache hasil pencarian /layanan (detik). */
    'search_cache_seconds' => (int) env('MARKETPLACE_SEARCH_CACHE_SECONDS', 180),

    /** Maks. tanggal libur muthowif yang di-load di halaman profil. */
    'profile_blocked_dates_limit' => (int) env('MARKETPLACE_PROFILE_BLOCKED_LIMIT', 60),

    /** Maks. portfolio + gambar di halaman profil (preview galeri). */
    'profile_portfolio_preview_limit' => (int) env('MARKETPLACE_PROFILE_PORTFOLIO_PREVIEW', 3),

    /** Maks. gambar per album portfolio di preview profil. */
    'profile_portfolio_images_limit' => (int) env('MARKETPLACE_PROFILE_PORTFOLIO_IMAGES', 12),
];
