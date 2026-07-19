<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $featuredMuthowifs = $featuredMuthowifs ?? collect();
    $latestArticles = $latestArticles ?? collect();
    $activeCampaigns = $activeCampaigns ?? collect();

    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null && is_dir(public_path('images/bg-welcome'))) {
        $entries = array_diff(scandir(public_path('images/bg-welcome')) ?: [], ['.', '..']);
        sort($entries, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($entries as $name) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
                $welcomeHeroBg = asset('images/bg-welcome/'.$name);
                break;
            }
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/welcome-hero.jpg'))
            ? asset('images/welcome-hero.jpg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $homeHelpHref = Auth::check() && Route::has('support.index')
        ? route('support.index')
        : ($contactLink ?: (Route::has('login') ? route('login') : route('layanan.index')));
@endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $homeSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name', 'Bayt-GO'),
                'url' => url('/'),
                'description' => 'Platform penghubung Muthowif profesional terverifikasi & jasa tour guide ibadah Umroh dan Haji.',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/layanan') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string'
                ]
            ];
            $homeDesc = "Temukan Muthowif terbaik dan jasa tour guide ibadah Umroh & Haji terpercaya di Bayt-GO. Bandingkan rating, ulasan, harga, dan pesan langsung asisten ibadah terverifikasi Anda secara mudah.";
        @endphp
        <x-seo-meta
            title="Jasa Tour Guide Ibadah Umroh & Haji | Muthowif Terpercaya"
            :description="$homeDesc"
            :schema="$homeSchema"
        />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-welcome antialiased text-slate-800 bg-slate-50/80 min-h-screen selection:bg-gold-light selection:text-baytgo-950">
        <div class="min-h-screen flex flex-col">
            <x-marketing-public-header active="welcome" />

            <main class="flex-1">
                @include('partials.home-app-feed', [
                    'homeHeroName' => Auth::check() ? Auth::user()->name : null,
                    'homeHelpHref' => $homeHelpHref,
                    'homeGuideCards' => [],
                    'welcomeHeroBg' => $welcomeHeroBg,
                    'featuredMuthowifs' => $featuredMuthowifs,
                    'latestArticles' => $latestArticles,
                    'activeCampaigns' => $activeCampaigns,
                    'galleryImages' => $galleryImages ?? collect(),
                    'showLandingChrome' => true,
                ])
            </main>

            @include('partials.site-footer')
        </div>
    </body>
</html>
