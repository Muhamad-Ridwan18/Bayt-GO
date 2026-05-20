<?php

return [
    'landing_pages' => [
        'jakarta' => [
            'title' => 'Muthowif Jakarta',
            'subtitle' => 'Temukan Muthowif profesional di Jakarta untuk Umroh dan Haji Anda.',
            'city' => 'Jakarta',
            'keywords' => ['muthowif jakarta', 'tour guide umroh jakarta', 'muthowif haji jakarta'],
        ],
        'madinah' => [
            'title' => 'Muthowif Madinah',
            'subtitle' => 'Muthowif berpengalaman di Madinah untuk pendamping ibadah Anda.',
            'city' => 'Madinah',
            'keywords' => ['muthowif madinah', 'tour guide umroh madinah', 'pendamping haji madinah'],
        ],
        'bahasa-indonesia' => [
            'title' => 'Muthowif Bahasa Indonesia',
            'subtitle' => 'Cari Muthowif yang mampu berbahasa Indonesia demi kenyamanan ibadah Anda.',
            'language' => 'Bahasa Indonesia',
            'keywords' => ['muthowif bahasa indonesia', 'tour guide umroh bahasa indonesia', 'muthowif indonesia'],
        ],
    ],
    'schema' => [
        'organization' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Bayt-GO'),
            'url' => config('app.url'),
            'logo' => config('app.url') . '/images/logo.png',
            'sameAs' => [
                'https://www.facebook.com',
                'https://www.instagram.com',
            ],
        ],
    ],
    'sitemap' => [
        'max_urls_per_file' => 500,
        'priorities' => [
            'home' => '1.0',
            'categories' => '0.9',
            'services' => '0.8',
            'articles' => '0.6',
        ],
        'changefreq' => [
            'home' => 'daily',
            'categories' => 'daily',
            'services' => 'weekly',
            'articles' => 'monthly',
        ],
    ],
];
