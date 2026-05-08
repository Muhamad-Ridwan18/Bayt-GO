@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifVerificationStatus;
    use App\Enums\PaymentStatus;
    use App\Enums\SupportTicketStatus;
    use App\Models\BookingReview;
    use App\Models\MuthowifBooking;
    use App\Models\MuthowifProfile;
    use App\Models\SupportTicket;

    $user = Auth::user();
    $userId = $user->getKey();

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

    $activeBookingCount = (int) MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
        ->count();

    $supportOpenCount = (int) SupportTicket::query()
        ->where('user_id', $userId)
        ->whereIn('status', [
            SupportTicketStatus::Open,
            SupportTicketStatus::InProgress,
            SupportTicketStatus::AwaitingCustomer,
        ])
        ->count();

    $completedTripCount = (int) MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->where('status', BookingStatus::Completed)
        ->count();

    $reviewsGivenCount = (int) BookingReview::query()
        ->where('customer_id', $userId)
        ->count();

    $nextBooking = MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->whereNotIn('status', [BookingStatus::Cancelled])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->with(['muthowifProfile.user'])
        ->first();

    $featuredMuthowifs = MuthowifProfile::query()
        ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price'])
        ->where('verification_status', MuthowifVerificationStatus::Approved)
        ->withCount('bookingReviews')
        ->withAvg('bookingReviews', 'rating')
        ->withCount(['bookings as completed_trips_count' => fn ($q) => $q->where('status', BookingStatus::Completed)])
        ->orderByDesc('booking_reviews_count')
        ->orderByDesc('verified_at')
        ->limit(8)
        ->get();

    $contactWaRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactWaRaw) ?? '';
    $contactWaLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $contactPhoneDisplay = config('app.contact_phone') ?: config('app.contact_whatsapp');
    $contactEmail = (string) (config('mail.from.address') ?? '');

    $customerGuideCards = __('dashboard.customer_guide_cards');
    if (! is_array($customerGuideCards)) {
        $customerGuideCards = [];
    }
@endphp

<div class="space-y-10 scroll-smooth">
    {{-- Hero: konsisten welcome — full bleed, kiri cream, foto ke kanan --}}
    <section class="relative left-1/2 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas pb-6 sm:pb-8 lg:pb-10">
        <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
            <img
                src="{{ $welcomeHeroBg }}"
                alt=""
                class="h-full w-full min-h-[20rem] object-cover object-[68%_26%] sm:min-h-[22rem] sm:object-[72%_28%] lg:min-h-[24rem] lg:object-[74%_28%]"
                loading="eager"
                decoding="async"
            />
        </div>
        <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/92 to-welcomeCanvas/38 sm:hidden" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[36%] via-welcomeCanvas/92 via-[60%] to-welcomeCanvas/10 sm:block lg:from-[40%] lg:via-[64%] lg:to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-16 bg-gradient-to-t from-welcomeCanvas via-welcomeCanvas/50 to-transparent sm:h-24" aria-hidden="true"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 pt-10 sm:px-6 sm:pt-12 lg:px-8 lg:pt-14">
            <div class="max-w-2xl">
                <p class="mb-5 inline-flex items-center gap-2.5 rounded-full border border-emerald-200/60 bg-emerald-50 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.1em] text-emerald-900">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    </span>
                    {{ __('dashboard.customer_hero_kicker') }}
                </p>
                <p class="text-xl font-semibold tracking-tight text-slate-800 sm:text-2xl">
                    {{ __('dashboard.customer_hero_intro') }}
                    <span class="font-bold text-baytgo">{{ $user->name }}</span>
                    <span aria-hidden="true">👋</span>
                </p>
                <p class="mt-4 max-w-lg text-[1.05rem] leading-relaxed text-slate-700 sm:text-lg">
                    {{ __('dashboard.customer_hero_sub') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-2.5">
                    @foreach (__('dashboard.customer_hero_badges') as $label)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-light/35 px-3 py-2 text-xs font-semibold text-baytgo ring-1 ring-gold/25">
                            <svg class="h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="relative z-20 mx-auto mt-10 max-w-7xl w-full px-4 sm:mt-11 sm:px-6 lg:mt-12 lg:px-8">
            <div class="rounded-3xl border border-gray-100/90 bg-white shadow-[0_24px_64px_-12px_rgba(15,42,37,0.12),0_12px_24px_-14px_rgba(0,0,0,0.06)]">
                @include('layanan.partials.date-search-form', [
                    'startDate' => '',
                    'endDate' => '',
                    'searchQuery' => '',
                    'showHeaderBanner' => false,
                    'welcomeAccent' => true,
                    'welcomeInlineHeader' => true,
                    'welcomeFlush' => true,
                ])
            </div>
        </div>
    </section>

    {{-- Konten utama: kiri direktori + panduan | kanan status & perjalanan --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-10 lg:col-span-8">
            <section aria-labelledby="customer-rec-heading">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="customer-rec-heading" class="text-xl font-bold tracking-tight text-baytgo sm:text-2xl">
                            {{ __('dashboard.customer_recommend_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.customer_recommend_sub') }}</p>
                    </div>
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gold-muted transition hover:text-baytgo">
                        {{ __('welcome.popular_see_all') }}
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </div>

                @if ($featuredMuthowifs->isEmpty())
                    <p class="rounded-2xl border border-dashed border-slate-200/90 bg-white py-14 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
                @else
                    <div class="relative max-w-[100vw] sm:max-w-none" x-data="{ scroll(dx) { const el = this.$refs.trackC; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                        <div class="-mx-1 flex gap-4 overflow-x-auto scroll-pl-4 pb-2 snap-x snap-mandatory px-1 sm:-mx-0 sm:flex-wrap sm:gap-5 sm:overflow-visible sm:pb-0 sm:snap-none" x-ref="trackC" style="-webkit-overflow-scrolling: touch;">
                            @foreach ($featuredMuthowifs as $profile)
                                @php
                                    $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                                    $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                                    $rating = $profile->booking_reviews_avg_rating;
                                    $ratingStr = $rating !== null ? number_format((float) $rating, 1) : '—';
                                    $reviewCount = (int) $profile->booking_reviews_count;
                                    $tripDone = (int) $profile->completed_trips_count;
                                    $languages = array_slice($profile->languagesForDisplay(), 0, 5);
                                    $langsLine = $languages !== [] ? implode(', ', $languages) : null;
                                @endphp
                                <article class="min-w-[15rem] max-w-[16.5rem] flex-shrink-0 snap-start overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:border-gold-muted/35 hover:shadow-md sm:min-w-0 sm:flex-1 sm:basis-[calc(33.333%-0.9rem)] sm:max-w-none">
                                    <a href="{{ route('layanan.show', $profile) }}" class="block h-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-gold focus-visible:ring-offset-2">
                                        <div class="relative h-[7.75rem] overflow-hidden bg-slate-50 sm:h-[8.25rem]">
                                            <img src="{{ route('layanan.photo', $profile) }}" alt="" class="h-full w-full object-cover object-[50%_12%]" loading="lazy" decoding="async" />
                                            <span class="absolute right-2.5 top-2.5 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[11px] font-bold shadow-sm ring-1 ring-gold/30">
                                                <svg class="h-3 w-3 text-gold-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                                <span class="tabular-nums text-baytgo">{{ $ratingStr }}</span>
                                                @if ($reviewCount > 0)
                                                    <span class="font-medium tabular-nums text-slate-500">({{ $reviewCount }})</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="border-t border-slate-100/90 p-3.5">
                                            <h3 class="font-bold leading-snug text-baytgo">
                                                <span class="line-clamp-1">{{ $profile->user->name ?? '—' }}</span>
                                            </h3>
                                            @if ($langsLine !== null)
                                                <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ __('dashboard.customer_langs_label') }} {{ $langsLine }}</p>
                                            @endif
                                            @if ($tripDone > 0)
                                                <p class="mt-1 text-[11px] text-slate-500">{{ trans_choice('dashboard.customer_trips_done', $tripDone, ['count' => $tripDone]) }}</p>
                                            @endif
                                            <p class="mt-2 text-sm font-semibold text-gold-muted">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                            <span class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-baytgo/20 bg-welcomeCanvas/50 py-2 text-[11px] font-semibold text-baytgo transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                                                {{ __('dashboard.customer_view_profile') }}
                                            </span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                        <div class="mt-3 flex justify-center gap-2 sm:hidden">
                            <button type="button" class="rounded-full border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-baytgo shadow-sm ring-1 ring-slate-100" @click="scroll(-260)">{{ __('welcome.carousel_prev') }}</button>
                            <button type="button" class="rounded-full border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-baytgo shadow-sm ring-1 ring-slate-100" @click="scroll(260)">{{ __('welcome.carousel_next') }}</button>
                        </div>
                    </div>
                @endif
            </section>

            @if ($customerGuideCards !== [])
                <section aria-labelledby="customer-guides-heading" class="pt-2">
                    <h2 id="customer-guides-heading" class="text-xl font-bold tracking-tight text-baytgo sm:text-2xl">
                        {{ __('dashboard.customer_content_title') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.customer_content_sub') }}</p>
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        @foreach ($customerGuideCards as $card)
                            <a href="{{ route('welcome') }}#{{ $card['fragment'] ?? '' }}" class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
                                <span class="inline-flex rounded-lg bg-gold-light/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo ring-1 ring-gold/25">{{ $card['read'] ?? '' }}</span>
                                <p class="mt-3 font-bold leading-snug text-baytgo group-hover:text-baytgo-800">{{ $card['title'] ?? '' }}</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $card['desc'] ?? '' }}</p>
                                <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-gold-muted group-hover:text-baytgo">
                                    {{ __('dashboard.customer_content_read') }}
                                    <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-4">
            <div class="relative overflow-hidden rounded-3xl bg-baytgo p-6 text-white shadow-[0_20px_40px_-14px_rgba(26,61,52,0.36)] ring-1 ring-white/10 sm:p-7">
                <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gold-muted/25 blur-2xl" aria-hidden="true"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-white/90">{{ __('dashboard.customer_status_title') }}</p>
                        <p class="mt-1 text-xs text-white/70">{{ __('dashboard.customer_status_sub') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-gold-light/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-gold-light ring-1 ring-gold-muted/35">{{ $user->role->label() }}</span>
                </div>
                <div class="relative mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        </span>
                        <p class="mt-3 text-2xl font-bold tabular-nums">{{ $activeBookingCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_active') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        </span>
                        <p class="mt-3 text-2xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_support') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <p class="mt-3 text-2xl font-bold tabular-nums">{{ $completedTripCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_completed') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.75.75 0 011.04 0l2.031 2.015a.75.75 0 001.069-.019l2.836-3.097a.75.75 0 011.063.962l-2.9 6.596a11.986 11.986 0 00-7.058 10.068H7.798a15.986 15.986 0 013.086-11.098L9.55 6.924a.75.75 0 01-.02-1.06l2.036-2.036a.76.76 0 01.019-.029zM7.07 21.125a24.086 24.086 0 013.986-17.068l-.375-.557a.752.752 0 011.068-1.031l7.096 10.097a27.986 27.986 0 01-11.774 9.559z" /></svg>
                        </span>
                        <p class="mt-3 text-2xl font-bold tabular-nums">{{ $reviewsGivenCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_reviews') }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="relative mt-6 flex items-center justify-center gap-2 rounded-xl bg-white/12 py-3 text-sm font-semibold text-white ring-1 ring-gold-muted/35 transition hover:bg-gold-muted/25 hover:ring-gold-muted/50">
                    {{ __('dashboard.customer_status_account_cta') }}
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-slate-100/90" aria-labelledby="customer-up-heading">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="customer-up-heading" class="text-base font-bold text-baytgo">{{ __('dashboard.customer_upcoming_title') }}</h2>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard.customer_upcoming_see_all') }}</a>
                </div>

                @if ($nextBooking === null)
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 py-10 text-center">
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
                        $dur = '';
                        if ($nb->starts_on && $nb->ends_on) {
                            $dur = max(1, (int) ($nb->starts_on->diffInDays($nb->ends_on) + 1));
                        }
                        $paid = $nb->payment_status === PaymentStatus::Paid;
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white ring-1 ring-slate-100/90">
                        <div class="grid grid-cols-[5.75rem_minmax(0,1fr)] gap-3.5 p-4 sm:grid-cols-[6.75rem_minmax(0,1fr)] sm:gap-4 sm:p-5">
                            <div class="relative h-[5.75rem] w-full shrink-0 overflow-hidden rounded-xl bg-welcomeCanvas ring-1 ring-gold-muted/25 sm:h-[7rem]">
                                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full object-cover object-[58%_28%]" loading="lazy" />
                            </div>
                            <div class="min-w-0 pt-1">
                                <p class="font-bold leading-snug text-slate-900">{{ $nb->service_type->label() }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-600">{{ __('dashboard.customer_with_guide', ['name' => $mpName]) }}</p>
                                <div class="mt-3 space-y-1.5 text-xs text-slate-600">
                                    <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_dates') }}</span> {{ $startStr }} — {{ $endStr }}</p>
                                    @if ($dur !== '')
                                        <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_duration') }}</span> {{ trans_choice('dashboard.customer_trip_days', $dur, ['count' => $dur]) }}</p>
                                    @endif
                                    <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_group') }}</span> {{ trans_choice('dashboard.customer_trip_pilgrims', (int) $nb->pilgrim_count, ['count' => (int) $nb->pilgrim_count]) }}</p>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $paid ? 'bg-gold-light/45 text-baytgo ring-1 ring-gold-muted/40' : 'bg-welcomeCanvas text-baytgo ring-1 ring-slate-200/80' }}">
                                        {{ $paid ? __('dashboard.customer_payment_paid') : $nb->payment_status->label() }}
                                    </span>
                                    <span class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ $nb->status->label() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-100/90 bg-welcomeCanvas/40 p-4 sm:p-5">
                            <a href="{{ route('bookings.show', $nb) }}" class="flex w-full items-center justify-center rounded-xl border border-baytgo/25 bg-white py-2.5 text-sm font-semibold text-baytgo shadow-sm shadow-baytgo/5 transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                                {{ __('dashboard.customer_booking_detail_cta') }}
                            </a>
                        </div>
                    </div>
                @endif
            </section>

            <div class="space-y-4">
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-slate-100/90">
                    <h3 class="font-bold text-baytgo">{{ __('dashboard.customer_help_ticket_title') }}</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ __('dashboard.customer_help_ticket_sub') }}</p>
                    @php
                        $supportHref = Route::has('support.create') ? route('support.create') : (Route::has('support.index') ? route('support.index') : null);
                    @endphp
                    @if ($supportHref)
                        <a href="{{ $supportHref }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-baytgo py-3 text-sm font-semibold text-white shadow-md shadow-baytgo/20 ring-1 ring-white/15 transition hover:bg-baytgo-800">{{ __('dashboard.customer_help_ticket_cta') }}</a>
                    @endif
                </div>
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-slate-100/90">
                    <h3 class="font-bold text-baytgo">{{ __('dashboard.customer_help_contact_title') }}</h3>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if ($contactWaLink)
                            <a href="{{ $contactWaLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-100/80 bg-emerald-50/90 text-baytgo ring-1 ring-emerald-100 transition hover:bg-emerald-50" aria-label="WhatsApp" title="WhatsApp">
                                <svg class="h-[1.375rem] w-[1.375rem]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                        @endif
                        @if ($contactPhoneDisplay)
                            @php $phoneHref = 'tel:'.preg_replace('/\s+/', '', (string) preg_replace('/[^\d+]/', '', (string) $contactPhoneDisplay)); @endphp
                            <a href="{{ $phoneHref }}" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200/90 bg-welcomeCanvas/80 text-baytgo ring-1 ring-gold-muted/20 transition hover:border-gold-muted/35 hover:bg-welcomeCanvas" aria-label="{{ __('dashboard.customer_contact_phone') }}" title="{{ __('dashboard.customer_contact_phone') }}">
                                <svg class="h-[1.375rem] w-[1.375rem]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.163-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                            </a>
                        @endif
                        @if ($contactEmail !== '')
                            <a href="mailto:{{ $contactEmail }}" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200/90 bg-white text-baytgo ring-1 ring-slate-100 transition hover:bg-welcomeCanvas" aria-label="Email" title="Email">
                                <svg class="h-[1.375rem] w-[1.375rem]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Pintasan ringkas (welcome-style pills) — mengganti grid akses cepat besar --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="{{ route('layanan.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-baytgo ring-1 ring-emerald-100/80">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            </span>
            <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_find_title') }}</p>
        </a>
        <a href="{{ route('bookings.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gold-light/35 text-baytgo ring-1 ring-gold/25">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_bookings_title') }}</p>
        </a>
        <a href="{{ route('profile.edit') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-welcomeCanvas text-baytgo ring-1 ring-gold-muted/30">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </span>
            <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_profile_title') }}</p>
        </a>
        @if (Route::has('support.index'))
            <a href="{{ route('support.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full border border-emerald-100 bg-emerald-50/70 text-baytgo">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </span>
                <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_support_title') }}</p>
            </a>
        @endif
    </div>
</div>
