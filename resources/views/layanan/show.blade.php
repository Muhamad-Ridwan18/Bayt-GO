@php
    use App\Enums\MuthowifServiceType;
    use Carbon\Carbon;

    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
    $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
    $avgRating = $profile->booking_reviews_avg_rating !== null ? round((float) $profile->booking_reviews_avg_rating, 1) : null;
    $confirmedBookings = (int) ($profile->confirmed_bookings_count ?? 0);
    $blockedCount = (int) ($profile->blocked_dates_count ?? $profile->blockedDates->count());

    $searchRangeLabel = null;
    if ($startDate !== '') {
        try {
            $endEff = $endDate !== '' ? $endDate : $startDate;
            $searchRangeLabel = Carbon::parse($startDate)->format('d/m/Y').' – '.Carbon::parse($endEff)->format('d/m/Y');
        } catch (\Throwable) {
            $searchRangeLabel = null;
        }
    }

    $initial = mb_substr($profile->user->name, 0, 1);
    $fallbackSvg = 'data:image/svg+xml,'.rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect fill="#e2e8f0" width="128" height="128"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="48" fill="#475569">'
        .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        .'</text></svg>'
    );

    $workExperiences = $profile->workExperiencesForDisplay();
    $experienceStat = $workExperiences[0] ?? '—';

    $prices = collect([$group?->daily_price, $private?->daily_price])->filter();
    $minPrice = $prices->min();

    $bookQueryParams = array_filter([
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'service_type' => is_string(request()->query('service_type')) && in_array(request()->query('service_type'), ['group', 'private'], true)
            ? request()->query('service_type')
            : null,
        'pilgrim_count' => is_numeric(request()->query('pilgrim_count')) && (int) request()->query('pilgrim_count') > 0
            ? (string) (int) request()->query('pilgrim_count')
            : null,
    ], fn ($v) => filled($v));

    $bookingPageUrl = route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams));
    $intent = $bookingIntent;
    $canBook = ($intent['can_submit'] ?? false) && ($group || $private);

    $indexQuery = array_filter([
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'q' => request()->query('q'),
        'service_type' => request()->query('service_type'),
        'pilgrim_count' => request()->query('pilgrim_count'),
    ], fn ($v) => filled($v));

    $muthowifSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $profile->user->name,
        'image' => $profile->photoUrl(),
        'description' => 'Jasa Muthowif profesional & tour guide ibadah Umroh dan Haji oleh '.$profile->user->name.' di Bayt-GO. Bandingkan rating, ulasan, dan pesan langsung.',
        'url' => route('layanan.show', $profile),
        'priceRange' => $minPrice ? 'IDR '.number_format($minPrice, 0, ',', '.') : 'Hubungi Kontak',
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'ID',
        ],
    ];

    if ($reviewsCount > 0 && $avgRating !== null) {
        $muthowifSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float) $avgRating, 1),
            'reviewCount' => $reviewsCount,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    $seoTitle = 'Muthowif '.$profile->user->name.' — Jasa Tour Guide Umroh & Haji Terpercaya';
    $seoDesc = 'Pesan jasa Muthowif '.$profile->user->name.' di Bayt-GO. Tour guide profesional terverifikasi untuk memandu ibadah Umroh & Haji Anda secara khusyuk dan aman.';
@endphp

<x-marketplace-layout :title="$seoTitle" :meta-description="$seoDesc" :schema="$muthowifSchema" wide>
    <div class="ui-marketplace-page-sticky">

        {{-- Breadcrumb + tanggal pencarian --}}
        <div class="ui-toolbar relative flex flex-wrap items-center justify-between gap-3">
            <nav class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1" aria-label="Breadcrumb">
                <a href="{{ route('layanan.index', $indexQuery) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('layanan.breadcrumb_find') }}
                </a>
                <span class="text-slate-300" aria-hidden="true">/</span>
                <span class="min-w-0 truncate font-medium text-slate-800">{{ $profile->user->name }}</span>
            </nav>
            @if ($searchRangeLabel)
                <a
                    href="{{ route('layanan.index', $indexQuery) }}"
                    class="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 transition hover:border-brand-300 hover:bg-brand-50"
                    title="{{ __('marketplace.show.change_dates') }}"
                >
                    <svg class="h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    <span class="truncate tabular-nums">{{ $searchRangeLabel }}</span>
                </a>
            @endif
        </div>

        @include('layanan.partials.profile-show-hero', [
            'profile' => $profile,
            'fallbackSvg' => $fallbackSvg,
            'experienceStat' => $experienceStat,
            'confirmedBookings' => $confirmedBookings,
            'reviewsCount' => $reviewsCount,
            'avgRating' => $avgRating,
            'canBook' => $canBook,
            'bookingPageUrl' => $bookingPageUrl,
        ])

        @include('layanan.partials.profile-booking-cta', [
            'profile' => $profile,
            'group' => $group,
            'private' => $private,
            'bookingIntent' => $bookingIntent,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'searchRangeLabel' => $searchRangeLabel,
        ])

        @include('layanan.partials.profile-show-packages', [
            'profile' => $profile,
            'group' => $group,
            'private' => $private,
            'bookQueryParams' => $bookQueryParams,
        ])

        @include('layanan.partials.profile-show-addons', [
            'group' => $group,
            'private' => $private,
        ])

        @include('layanan.partials.profile-show-reviews', [
            'profile' => $profile,
            'reviewsCount' => $reviewsCount,
            'avgRating' => $avgRating,
        ])

        <details class="group rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 open:ring-brand-200/60">
            <summary class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-slate-900 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-3">
                    <span>{{ __('marketplace.show.more_about_heading') }}</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </span>
            </summary>
            <div class="ui-stack-compact border-t border-slate-100 px-5 py-5">
                @include('layanan.partials.profile-show-bottom', [
                    'profile' => $profile,
                    'group' => $group,
                    'private' => $private,
                ])

                @include('layanan.partials.profile-show-trust-bar')
            </div>
        </details>

        @if ($blockedCount > 0)
            <details class="rounded-2xl border border-amber-200/70 bg-white shadow-sm ring-1 ring-amber-100/60">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-900">
                    {{ __('marketplace.show.summary_blocked', ['count' => $blockedCount]) }}
                </summary>
                <div class="border-t border-amber-100/80 px-5 py-4">
                    <p class="mb-3 text-xs text-slate-600">{{ __('marketplace.show.blocked_sub') }}</p>
                    <ul class="grid gap-2 text-xs sm:grid-cols-2">
                        @foreach ($profile->blockedDates as $bd)
                            <li class="rounded-lg border border-amber-100 bg-amber-50/50 px-3 py-2">
                                <span class="font-semibold tabular-nums text-slate-900">{{ $bd->blocked_on->format('d/m/Y') }}</span>
                                @if (filled($bd->note))
                                    <span class="mt-0.5 block text-slate-600">{{ $bd->note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </details>
        @endif

        @include('layanan.partials.profile-show-sticky-cta', [
            'profile' => $profile,
            'canBook' => $canBook,
            'bookingPageUrl' => $bookingPageUrl,
            'searchRangeLabel' => $searchRangeLabel,
        ])
    </div>
</x-marketplace-layout>
