<?php

return [

    /** Detik cache data halaman welcome (featured, galeri, artikel, campaign). */
    'cache_seconds' => (int) env('WELCOME_PAGE_CACHE_SECONDS', 120),

    /** Jumlah foto di strip galeri welcome. */
    'gallery_limit' => (int) env('WELCOME_GALLERY_LIMIT', 30),

];
