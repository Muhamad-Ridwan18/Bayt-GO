@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $rtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo-meta 
            :title="__('terms.page_title') . ' — Aturan Penggunaan'" 
            description="Syarat dan Ketentuan penggunaan layanan platform Bayt-GO untuk jamaah, Muthowif, dan asisten tour guide ibadah Umroh & Haji." 
        />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @if ($rtl)
            <link href="https://fonts.bunny.net/css?family=noto-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .terms-content { font-family: {{ $rtl ? "'Noto Sans Arabic', " : '' }}"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body class="font-sans antialiased text-slate-800 bg-slate-50 min-h-screen terms-content">
        <div class="min-h-screen flex flex-col">
            <header class="sticky top-0 z-20 border-b border-white/10 bg-slate-900/95 backdrop-blur-md text-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 group shrink-0">
                        <x-site-logo variant="welcome" />
                        <span class="text-lg font-bold tracking-tight">Bayt<span class="text-brand-300">Go</span></span>
                    </a>
                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        <x-language-switcher variant="segment-dark" />
                        <nav class="flex items-center text-sm whitespace-nowrap">
                            <a href="{{ url('/') }}" class="px-3 py-2 rounded-xl font-medium text-white/90 hover:bg-white/10 transition">{{ __('nav.home') }}</a>
                        </nav>
                    </div>
                </div>
            </header>

            <main class="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 py-10 sm:py-14">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700 mb-2">{{ config('app.name') }}</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6">{{ __('terms.page_title') }}</h1>
                <p class="text-slate-600 leading-relaxed mb-10">{{ __('terms.intro') }}</p>

                <div class="space-y-10 text-slate-700">
                    @foreach (__('terms.sections') as $index => $section)
                        @if (is_array($section))
                            <section aria-labelledby="term-section-{{ $index }}">
                                <h2 id="term-section-{{ $index }}" class="text-lg font-bold text-slate-900 mb-3">
                                    <span class="text-brand-600 font-semibold">{{ $loop->iteration }}.</span>
                                    {{ $section['title'] ?? '' }}
                                </h2>
                                @foreach ($section['paragraphs'] ?? [] as $paragraph)
                                    <p class="leading-relaxed mb-4 last:mb-0">{{ $paragraph }}</p>
                                @endforeach
                                @if (! empty($section['bullets']))
                                    <ul class="list-disc ps-5 space-y-2 mt-2">
                                        @foreach ($section['bullets'] as $item)
                                            <li class="leading-relaxed">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </section>
                        @endif
                    @endforeach
                </div>
            </main>

            <footer class="border-t border-slate-200 bg-white mt-auto">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    <div class="flex flex-col items-center gap-2 sm:items-end">
                        <x-language-switcher variant="compact" />
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                            </a>
                        @endif
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
