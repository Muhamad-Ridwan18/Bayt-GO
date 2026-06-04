<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kompresi upload gambar
    |--------------------------------------------------------------------------
    |
    | Semua foto/raster dari user di-resize (scale down) lalu disimpan sebagai
    | JPEG agar hemat storage & bandwidth. PDF dan GIF tidak diubah.
    |
    */

    'enabled' => (bool) env('IMAGE_UPLOAD_OPTIMIZE', true),

    'format' => 'jpg',

    'profiles' => [
        'default' => [
            'max_width' => (int) env('IMAGE_UPLOAD_MAX_WIDTH', 1920),
            'max_height' => (int) env('IMAGE_UPLOAD_MAX_HEIGHT', 1920),
            'quality' => (int) env('IMAGE_UPLOAD_JPEG_QUALITY', 82),
        ],
        'profile' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 82,
        ],
        'document' => [
            'max_width' => 2048,
            'max_height' => 2048,
            'quality' => 85,
        ],
        'portfolio' => [
            'max_width' => 1920,
            'max_height' => 1920,
            'quality' => 82,
        ],
        'chat' => [
            'max_width' => 1280,
            'max_height' => 1280,
            'quality' => 80,
        ],
        'attachment' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 82,
        ],
        'banner' => [
            'max_width' => 2400,
            'max_height' => 2400,
            'quality' => 85,
        ],
        'content' => [
            'max_width' => 1920,
            'max_height' => 1920,
            'quality' => 82,
        ],
    ],

];
