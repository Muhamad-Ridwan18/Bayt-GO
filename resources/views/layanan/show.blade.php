@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
    $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
    $avgRating = $profile->booking_reviews_avg_rating !== null ? round((float) $profile->booking_reviews_avg_rating, 1) : null;
    $confirmedBookings = (int) ($profile->confirmed_bookings_count ?? 0);
    $blockedCount = $profile->blockedDates->count();

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

    $hasBackground = $profile->educationsForDisplay() !== [] || $profile->workExperiencesForDisplay() !== [];
    $canSubmitBooking = ($bookingIntent['can_submit'] ?? false) && ($group || $private);
@endphp

<x-marketplace-layout :title="$profile->user->name">
    {{-- overflow-hidden: blob dekoratif (-left/-right) jangan memperlebar scroll horizontal di mobile --}}
    <div class="relative min-w-0 space-y-6 overflow-x-hidden">
        <div class="pointer-events-none absolute -left-24 top-0 h-64 w-64 rounded-full bg-brand-200/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 top-24 h-56 w-56 rounded-full bg-amber-200/15 blur-3xl" aria-hidden="true"></div>

        {{-- Satu bar atas: kembali + nama + tanggal (ringkas) --}}
        <div class="relative flex flex-wrap items-center gap-x-3 gap-y-2 rounded-2xl border border-slate-200/80 bg-white/95 px-3 py-2.5 text-sm shadow-sm ring-1 ring-slate-100/80 sm:px-4">
            <a href="{{ route('layanan.index', array_filter(request()->only(['start_date', 'end_date', 'q']))) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-800">
                <svg class="h-4 w-4 shrink-0 opacity-80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                </svg>
                {{ __('layanan.breadcrumb_find') }}
            </a>
            <span class="hidden text-slate-300 sm:inline" aria-hidden="true">·</span>
            <span class="min-w-0 font-medium text-slate-800 truncate">{{ $profile->user->name }}</span>
            @if ($searchRangeLabel)
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">·</span>
                <span class="inline-flex max-w-full items-center gap-1 rounded-lg bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-900 ring-1 ring-brand-200/70">
                    <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    <span class="truncate">{{ $searchRangeLabel }}</span>
                </span>
            @endif
        </div>

        @if ($canSubmitBooking)
            <div class="flex flex-col gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/95 via-white to-white px-4 py-3 shadow-sm ring-1 ring-emerald-100/70 sm:flex-row sm:items-center sm:justify-between sm:px-5 sm:py-3.5" role="status">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-emerald-950">{{ __('marketplace.show.booking_ready_title') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-emerald-900/90">{{ __('marketplace.show.booking_ready_steps') }}</p>
                </div>
                <a href="#booking-box" class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/15 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 lg:hidden">
                    {{ __('marketplace.show.scroll_to_booking') }}
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v9.546l2.955-3.084a.75.75 0 111.09 1.03l-4.25 4.442a.75.75 0 01-1.09 0L5.22 11.243a.75.75 0 011.09-1.03l2.955 3.084V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        @endif

        {{--
          Mobile: profil → booking → sisanya (pendek).
          lg: profil + konten di kiri, booking menempel di kanan (grid area).
        --}}
        <div
            class="grid min-w-0 grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr),min(22rem,100%)] lg:items-start lg:gap-8 [grid-template-areas:'prof'_'book'_'rest'] lg:[grid-template-areas:'prof_book'_'rest_book']"
        >
            {{-- Profil ringkas --}}
            <div class="[grid-area:prof]">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-md ring-1 ring-slate-100/80">
                    <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brand-400 via-brand-500 to-amber-400" aria-hidden="true"></span>
                    <div class="relative flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:gap-5 sm:p-5">
                        <div class="relative mx-auto shrink-0 sm:mx-0">
                            <img
                                src="{{ route('layanan.photo', $profile) }}"
                                alt="{{ $profile->user->name }}"
                                width="112"
                                height="112"
                                class="h-24 w-24 rounded-2xl object-cover shadow-md ring-2 ring-white sm:h-28 sm:w-28"
                                loading="eager"
                                onerror="this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}"
                            >
                            <span class="absolute -bottom-1 -right-1 inline-flex items-center gap-0.5 rounded-full bg-emerald-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white shadow-md ring-2 ring-white" title="{{ __('marketplace.card.verified_title') }}">
                                <svg class="h-2.5 w-2.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                {{ __('marketplace.show.verified_badge') }}
                            </span>
                        </div>
                        <div class="min-w-0 flex-1 text-center sm:text-left">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">{{ __('marketplace.show.kicker') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $profile->user->name }}</h1>
                            <p class="mt-0.5 text-xs text-slate-600 sm:text-sm">{{ __('marketplace.show.tagline') }}</p>
                            <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                @if ($reviewsCount > 0 && $avgRating !== null)
                                    <span class="flex gap-0.5" aria-hidden="true">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <svg class="h-3.5 w-3.5 {{ $s <= (int) round($avgRating) ? 'text-amber-400' : 'text-slate-200' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                        @endfor
                                    </span>
                                    <span class="text-xs font-semibold text-slate-800">{{ __('marketplace.card.reviews_line', ['rating' => $avgRating, 'count' => $reviewsCount]) }}</span>
                                @endif
                                @if ($confirmedBookings > 0)
                                    <span class="text-xs text-slate-500">· {{ __('marketplace.card.bookings_confirmed', ['count' => $confirmedBookings]) }}</span>
                                @elseif ($reviewsCount === 0)
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-900 ring-1 ring-amber-200/80">{{ __('marketplace.card.new_marketplace') }}</span>
                                @endif
                            </div>
                            @if ($profile->languagesForDisplay() !== [])
                                <p class="mt-2 flex flex-wrap justify-center gap-1.5 sm:justify-start">
                                    @foreach (array_slice($profile->languagesForDisplay(), 0, 5) as $lang)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 ring-1 ring-slate-200/70">{{ $lang }}</span>
                                    @endforeach
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Booking: di mobile langsung di bawah profil; di lg kolom kanan menempel --}}
            <aside id="booking-box" class="[grid-area:book] scroll-mt-24 lg:sticky lg:top-24 lg:self-start">
                @include('layanan.partials.booking-panel', [
                    'profile' => $profile,
                    'group' => $group,
                    'private' => $private,
                    'bookingIntent' => $bookingIntent,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ])
            </aside>

            {{-- Detail panjang: ringkasan tetap terlihat, sisanya di <details> --}}
            <div class="[grid-area:rest] space-y-4">
                <div class="flex items-center gap-2 px-0.5 pt-1">
                    <span class="h-px min-w-[1.25rem] flex-1 bg-slate-200/90 sm:min-w-[2rem]" aria-hidden="true"></span>
                    <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ __('marketplace.show.more_about_heading') }}</h2>
                    <span class="h-px flex-1 bg-slate-200/90" aria-hidden="true"></span>
                </div>
                @if ($group || $private)
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 sm:p-5">
                        <h2 class="text-base font-bold text-slate-900">{{ __('marketplace.show.packages_heading') }}</h2>
                        <p class="mt-1 text-xs text-slate-600 sm:text-sm">{{ __('marketplace.show.packages_compact_hint') }}</p>
                        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                            @if ($group)
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 ring-1 ring-slate-100/80">
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-brand-800">{{ MuthowifServiceType::Group->label() }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">
                                        @if ($group->daily_price !== null)
                                            Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}<span class="text-xs font-normal text-slate-500">{{ __('marketplace.show.per_day') }}</span>
                                        @else
                                            <span class="text-slate-500">{{ __('marketplace.card.price_contact') }}</span>
                                        @endif
                                    </dd>
                                    @if ($group->min_pilgrims && $group->max_pilgrims)
                                        <dd class="mt-0.5 text-xs text-slate-600">{{ __('marketplace.show.pilgrim_range', ['min' => $group->min_pilgrims, 'max' => $group->max_pilgrims]) }}</dd>
                                    @endif
                                </div>
                            @endif
                            @if ($private)
                                <div class="rounded-xl border border-amber-100/90 bg-amber-50/40 px-3 py-2.5 ring-1 ring-amber-100/80">
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-amber-950">{{ MuthowifServiceType::PrivateJamaah->label() }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">
                                        @if ($private->daily_price !== null)
                                            Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}<span class="text-xs font-normal text-slate-500">{{ __('marketplace.show.per_day') }}</span>
                                        @else
                                            <span class="text-slate-500">{{ __('marketplace.card.price_contact') }}</span>
                                        @endif
                                    </dd>
                                    @if ($private->min_pilgrims && $private->max_pilgrims)
                                        <dd class="mt-0.5 text-xs text-slate-600">{{ __('marketplace.show.pilgrim_range', ['min' => $private->min_pilgrims, 'max' => $private->max_pilgrims]) }}</dd>
                                    @endif
                                </div>
                            @endif
                        </dl>

                        <details class="group mt-4 rounded-xl border border-slate-200 bg-white open:bg-slate-50/30 open:shadow-sm">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5 text-sm font-semibold text-brand-800 hover:bg-slate-50/80 [&::-webkit-details-marker]:hidden">
                                <span>{{ __('marketplace.show.summary_packages') }}</span>
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                            </summary>
                            <div class="space-y-5 border-t border-slate-100 px-3 py-4 text-sm">
                                @if ($group)
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-brand-800">{{ __('marketplace.show.group_title') }}</p>
                                        @if (filled($group->name))
                                            <p class="mt-1 font-medium text-slate-800">{{ $group->name }}</p>
                                        @endif
                                        @if (filled($group->description))
                                            <p class="mt-2 whitespace-pre-line text-slate-600">{{ $group->description }}</p>
                                        @endif
                                        <ul class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                            @if (($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0)
                                                <li class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ __('marketplace.show.addon_same_hotel', ['price' => IndonesianNumber::formatThousands((string) (int) $group->same_hotel_price_per_day)]) }}</li>
                                            @endif
                                            @if (($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0)
                                                <li class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ __('marketplace.show.addon_transport', ['price' => IndonesianNumber::formatThousands((string) (int) $group->transport_price_flat)]) }}</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                                @if ($private)
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-amber-900">{{ __('marketplace.show.private_title') }}</p>
                                        @if (filled($private->name))
                                            <p class="mt-1 font-medium text-slate-800">{{ $private->name }}</p>
                                        @endif
                                        @if (filled($private->description))
                                            <p class="mt-2 whitespace-pre-line text-slate-600">{{ $private->description }}</p>
                                        @endif
                                        <ul class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                            @if (($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0)
                                                <li class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ __('marketplace.show.addon_same_hotel', ['price' => IndonesianNumber::formatThousands((string) (int) $private->same_hotel_price_per_day)]) }}</li>
                                            @endif
                                            @if (($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0)
                                                <li class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ __('marketplace.show.addon_transport', ['price' => IndonesianNumber::formatThousands((string) (int) $private->transport_price_flat)]) }}</li>
                                            @endif
                                        </ul>
                                        @if ($private->addOns->isNotEmpty())
                                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.addon_heading') }}</p>
                                            <ul class="mt-1.5 space-y-1.5">
                                                @foreach ($private->addOns as $addon)
                                                    <li class="flex justify-between gap-2 rounded-lg bg-amber-50/80 px-2.5 py-1.5 text-xs ring-1 ring-amber-100/80">
                                                        <span class="font-medium text-slate-800">{{ $addon->name }}</span>
                                                        <span class="shrink-0 font-bold text-amber-900">Rp {{ IndonesianNumber::formatThousands((string) (int) $addon->price) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </details>
                    </div>
                @endif

                @if ($hasBackground)
                    <details class="group rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-100/80 open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ __('marketplace.show.summary_background') }}</span>
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </summary>
                        <div class="space-y-4 border-t border-slate-100 px-4 py-4">
                            @if ($profile->educationsForDisplay() !== [])
                                <x-line-list :label="__('marketplace.show.education')" :items="$profile->educationsForDisplay()" />
                            @endif
                            @if ($profile->workExperiencesForDisplay() !== [])
                                <x-line-list :label="__('marketplace.show.experience')" :items="$profile->workExperiencesForDisplay()" />
                            @endif
                        </div>
                    </details>
                @endif

                <details class="group rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-100/80 open:shadow-md">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                        <span>
                            @if ($reviewsCount > 0)
                                {{ __('marketplace.show.summary_reviews', ['count' => $reviewsCount]) }}
                                @if ($avgRating !== null)
                                    <span class="ml-1 font-normal text-slate-500">({{ $avgRating }} ★)</span>
                                @endif
                            @else
                                {{ __('marketplace.show.summary_reviews_none') }}
                            @endif
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </summary>
                    <div class="border-t border-slate-100 px-4 py-4">
                        @if ($profile->bookingReviews->isEmpty())
                            <p class="text-sm text-slate-600">{{ __('marketplace.show.no_reviews') }}</p>
                        @else
                            @if ($avgRating !== null)
                                <p class="mb-3 text-xs text-slate-600">{{ __('marketplace.show.reviews_avg', ['rating' => $avgRating, 'count' => $reviewsCount]) }}</p>
                            @endif
                            <ul class="space-y-2">
                                @foreach ($profile->bookingReviews as $review)
                                    <li class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-slate-900">{{ $review->customer?->name ?? __('marketplace.show.review_anonymous') }}</span>
                                            <span class="text-[11px] font-bold text-amber-800">{{ $review->rating }} ★</span>
                                        </div>
                                        @if (filled($review->review))
                                            <p class="mt-1.5 text-xs leading-relaxed text-slate-700">{{ Str::limit($review->review, 280) }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-slate-500">{{ $review->created_at?->format('d/m/Y') }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </details>

                <details class="group rounded-2xl border border-amber-200/70 bg-gradient-to-br from-amber-50/50 to-white shadow-sm ring-1 ring-amber-100/60 open:shadow-md">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                        <span>
                            @if ($blockedCount > 0)
                                {{ __('marketplace.show.summary_blocked', ['count' => $blockedCount]) }}
                            @else
                                {{ __('marketplace.show.summary_blocked_none') }}
                            @endif
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-amber-700/60 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </summary>
                    <div class="border-t border-amber-100/80 px-4 py-4">
                        <p class="mb-3 text-xs text-slate-600">{{ __('marketplace.show.blocked_sub') }}</p>
                        @if ($profile->blockedDates->isEmpty())
                            <p class="text-sm text-slate-600">{{ __('marketplace.show.blocked_empty') }}</p>
                        @else
                            <ul class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                @foreach ($profile->blockedDates as $bd)
                                    <li class="rounded-lg border border-amber-100/90 bg-white/90 px-2.5 py-2">
                                        <span class="font-semibold tabular-nums text-slate-900">{{ $bd->blocked_on->format('d/m/Y') }}</span>
                                        @if (filled($bd->note))
                                            <span class="mt-0.5 block text-slate-600">{{ $bd->note }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>
</x-marketplace-layout>
