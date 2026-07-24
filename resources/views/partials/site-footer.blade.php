@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp

<footer class="mt-auto border-t border-slate-200 bg-white @auth{{ Auth::check() && Auth::user()->isCustomer() ? 'pb-24 lg:pb-0' : '' }}@endauth">
    <x-page-container class="grid gap-8 py-10 text-center text-sm text-slate-600 sm:grid-cols-2 sm:text-left lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 sm:justify-start">
                <x-site-logo variant="welcome" class="rounded-lg" />
                <span class="text-lg font-bold text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
            </a>
            <p class="mx-auto mt-3 max-w-xs text-xs leading-relaxed text-slate-500 sm:mx-0">{{ __('welcome.about_sub') }}</p>
            <p class="mt-4 text-xs text-slate-400">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-900">{{ __('welcome.nav_about') }}</p>
            <ul class="mt-3 space-y-2 text-xs">
                <li><a href="{{ route('welcome') }}#cara-kerja" class="hover:text-baytgo">{{ __('welcome.nav_how') }}</a></li>
                <li><a href="{{ route('welcome') }}#faq" class="hover:text-baytgo">{{ __('welcome.nav_faq') }}</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-baytgo">{{ __('terms.footer_link') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-900">{{ __('welcome.landing_service') }}</p>
            <ul class="mt-3 space-y-2 text-xs">
                <li><a href="{{ route('layanan.index') }}" class="hover:text-baytgo">{{ __('dashboard.customer_cat_umroh') }}</a></li>
                <li><a href="{{ route('layanan-pendukung.index', ['category' => 'mobility']) }}" class="hover:text-baytgo">{{ __('dashboard.customer_cat_wheelchair') }}</a></li>
                <li><a href="{{ route('layanan-pendukung.index', ['category' => 'umrah']) }}" class="hover:text-baytgo">{{ __('dashboard.customer_cat_prayer') }}</a></li>
                <li><a href="{{ route('layanan-pendukung.index', ['category' => 'other']) }}" class="hover:text-baytgo">{{ __('dashboard.customer_cat_photo') }}</a></li>
                <li><a href="{{ route('layanan-pendukung.index', ['category' => 'ziarah']) }}" class="hover:text-baytgo">{{ __('dashboard.customer_cat_raudho') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-900">{{ __('nav.contact_us') }}</p>
            <ul class="mt-3 space-y-2 text-xs">
                @if ($contactLink)
                    <li>
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="hover:text-baytgo">
                            {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                        </a>
                    </li>
                @endif
                <li><a href="{{ route('articles.index') }}" class="hover:text-baytgo">{{ __('nav.articles') }}</a></li>
            </ul>
        </div>
    </x-page-container>
</footer>
