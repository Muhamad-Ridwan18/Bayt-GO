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
    <body class="font-welcome antialiased text-slate-800 bg-gradient-to-b from-welcomeCanvas via-white to-slate-50/80 min-h-screen selection:bg-gold-light selection:text-baytgo-950">
        <div class="min-h-screen flex flex-col">
            <x-marketing-public-header active="welcome" />

            <main class="flex-1">
                <x-page-container class="ui-stack-tight relative py-6 sm:py-8">
                    @include('partials.home-app-feed', [
                        'homeHeroName' => Auth::check() ? Auth::user()->name : null,
                        'homeHelpHref' => $homeHelpHref,
                        'homeGuideCards' => [],
                        'welcomeHeroBg' => $welcomeHeroBg,
                        'featuredMuthowifs' => $featuredMuthowifs,
                        'latestArticles' => $latestArticles,
                        'activeCampaigns' => $activeCampaigns,
                    ])

                    <section class="mt-10 scroll-mt-24" id="cara-kerja" aria-labelledby="welcome-work-heading">
                        <div class="mb-5 text-center sm:mb-6">
                            <h2 id="welcome-work-heading" class="text-lg font-bold text-baytgo sm:text-xl">{{ __('welcome.work_title') }}</h2>
                            <p class="mt-1.5 text-sm text-slate-600">{{ __('welcome.work_sub') }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                            @foreach (__('welcome.work_steps') as $i => $step)
                                <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 sm:p-5">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-700">{{ $i + 1 }}</span>
                                    <h3 class="mt-3 text-sm font-bold text-slate-900">{{ $step['title'] }}</h3>
                                    <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $step['desc'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="mt-10 scroll-mt-24" id="faq" aria-labelledby="welcome-faq-heading">
                        <h2 id="welcome-faq-heading" class="mb-5 text-lg font-bold text-baytgo sm:text-xl">{{ __('welcome.faq_title') }}</h2>
                        <dl class="space-y-3">
                            @foreach (array_slice(__('welcome.faq_items'), 0, 4) as $item)
                                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 sm:p-5">
                                    <dt class="text-sm font-semibold text-slate-900">{{ $item['q'] }}</dt>
                                    <dd class="mt-1.5 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ $item['a'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section class="mt-10 mb-4 scroll-mt-24" id="tentang">
                        <div class="relative overflow-hidden rounded-3xl bg-baytgo p-5 text-white shadow-lg sm:p-6">
                            <div class="relative max-w-xl">
                                <h2 class="text-lg font-bold sm:text-xl">{{ __('welcome.final_cta_title') }}</h2>
                                <p class="mt-2 text-sm text-white/85">{{ __('welcome.final_cta_sub') }}</p>
                                <a href="{{ route('layanan.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-gold px-4 py-2.5 text-sm font-bold text-baytgo-950 shadow transition hover:bg-gold-muted">
                                    {{ __('welcome.final_cta_button') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                                </a>
                            </div>
                        </div>
                    </section>
                    <div id="harga" class="sr-only" aria-hidden="true"></div>
                </x-page-container>
            </main>

            <footer class="mt-auto border-t border-slate-200 bg-white">
                <x-page-container class="flex flex-col gap-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-start sm:gap-4">
                        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                        <a href="{{ route('terms') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('terms.footer_link') }}</a>
                    </div>
                    <div class="flex flex-col items-center gap-1 sm:items-end">
                        <span class="text-xs text-slate-400">{{ __('welcome.footer_tagline') }}</span>
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-baytgo hover:text-baytgo-800">
                                {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                            </a>
                        @endif
                    </div>
                </x-page-container>
            </footer>
        </div>
    </body>
</html>
