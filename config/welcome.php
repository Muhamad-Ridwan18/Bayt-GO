<?php

return [

    /** Detik cache data halaman welcome (featured, galeri, artikel, campaign). */
    'cache_seconds' => (int) env('WELCOME_PAGE_CACHE_SECONDS', 120),

    /** Jumlah foto di strip galeri welcome. */
    'gallery_limit' => (int) env('WELCOME_GALLERY_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Welcome landing hero defaults
    |--------------------------------------------------------------------------
    |
    | Unggahan / URL luar / posisi foto diatur di Admin → Tampilan situs (site_settings).
    | Fallback gambar digunakan jika tidak ada file di public/images/bg-welcome* dan
    | tidak ada kustom dari admin.
    |
    */

    'hero' => [
        'fallback_image' => 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop',

        'object_position' => [
            'base' => '72% 26%',
            'sm' => '75% 28%',
            'lg' => '78% 30%',
        ],
    ],

];
