@php
    use App\Enums\PaymentStatus;
@endphp

<div>
    @include('partials.home-app-feed', [
        'homeHeroName' => $user->name,
        'homeHelpHref' => Route::has('support.index') ? route('support.index') : route('layanan.index'),
        'homeGuideCards' => $customerGuideCards ?? [],
        'welcomeHeroBg' => $welcomeHeroBg,
        'featuredMuthowifs' => $featuredMuthowifs,
        'latestArticles' => $latestArticles,
        'activeCampaigns' => $activeCampaigns ?? collect(),
        'galleryImages' => $galleryImages ?? collect(),
        'showLandingChrome' => true,
    ])

    <aside class="grid gap-5 border-t border-slate-100 px-4 pt-8 sm:px-6 lg:grid-cols-2 lg:px-8 xl:px-10">
        <div class="relative overflow-hidden rounded-3xl bg-baytgo p-5 text-white shadow-[0_20px_40px_-14px_rgba(26,61,52,0.35)] ring-1 ring-white/10 sm:p-6">
            <div class="relative">
                <p class="text-sm font-bold text-white">{{ __('dashboard.customer_status_title') }}</p>
                <p class="mt-1 text-xs text-white/75">{{ __('dashboard.customer_status_sub') }}</p>
            </div>
            <div class="relative mt-5 grid grid-cols-2 gap-3">
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 transition hover:bg-white/15">
                    <p class="text-2xl font-bold tabular-nums">{{ $activeBookingCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_active') }}</p>
                </a>
                @if (Route::has('support.index'))
                    <a href="{{ route('support.index') }}" class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 transition hover:bg-white/15">
                        <p class="text-2xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_support') }}</p>
                    </a>
                @else
                    <div class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15">
                        <p class="text-2xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium text-white/85">{{ __('dashboard.customer_stat_support') }}</p>
                    </div>
                @endif
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 transition hover:bg-white/15">
                    <p class="text-2xl font-bold tabular-nums">{{ $upcomingTripCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_upcoming') }}</p>
                </a>
                <div class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15">
                    <p class="text-2xl font-bold tabular-nums">{{ $reviewsGivenCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_reviews') }}</p>
                </div>
            </div>
        </div>

        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/90" aria-labelledby="customer-up-heading">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 id="customer-up-heading" class="text-base font-bold text-baytgo">{{ __('dashboard.customer_upcoming_title') }}</h2>
                <a href="{{ route('bookings.index') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard.customer_upcoming_see_all') }}</a>
            </div>

            @if ($nextBooking === null)
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 py-8 text-center">
                    <p class="text-sm font-medium text-slate-700">{{ __('dashboard.customer_upcoming_empty') }}</p>
                    <a href="{{ route('layanan.index') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                        {{ __('dashboard.customer_upcoming_cta') }}
                    </a>
                </div>
            @else
                @php
                    $nb = $nextBooking;
                    $mpName = $nb->muthowifProfile?->user?->name ?? '—';
                    $startStr = $nb->starts_on?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '';
                    $endStr = $nb->ends_on?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '';
                    $paid = $nb->payment_status === PaymentStatus::Paid;
                @endphp
                <div class="overflow-hidden rounded-2xl border border-slate-100 ring-1 ring-slate-100/90">
                    <div class="grid grid-cols-[5rem_minmax(0,1fr)] gap-3 p-3.5">
                        <div class="relative h-[5rem] overflow-hidden rounded-xl bg-slate-100">
                            @if ($nb->muthowifProfile)
                                <img src="{{ $nb->muthowifProfile->photoUrl() }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold leading-snug text-slate-900">{{ $nb->service_type->label() }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ __('dashboard.customer_with_guide', ['name' => $mpName]) }}</p>
                            <p class="mt-2 text-xs text-slate-600">{{ $startStr }} — {{ $endStr }}</p>
                            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $paid ? 'bg-gold-light/45 text-baytgo ring-1 ring-gold-muted/40' : 'bg-welcomeCanvas text-baytgo ring-1 ring-slate-200' }}">
                                {{ $paid ? __('dashboard.customer_payment_paid') : $nb->payment_status->label() }}
                            </span>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 bg-welcomeCanvas/50 p-3">
                        <a href="{{ route('bookings.show', $nb) }}" class="flex w-full items-center justify-center rounded-xl border border-baytgo/25 bg-white py-2.5 text-sm font-semibold text-baytgo transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                            {{ __('dashboard.customer_booking_detail_cta') }}
                        </a>
                    </div>
                </div>
            @endif
        </section>
    </aside>
</div>
