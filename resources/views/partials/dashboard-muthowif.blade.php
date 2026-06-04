@php
    use App\Enums\BookingStatus;
    use App\Models\BookingChatMessage;
    use App\Models\MuthowifProfile;
    use Carbon\Carbon;
    use App\Support\IndonesianNumber;
    use App\Services\MuthowifDashboardCalendarDataBuilder;

    $mp = MuthowifProfile::query()
        ->whereKey(Auth::user()->muthowifProfile->getKey())
        ->withMarketplaceStats()
        ->withCount([
            'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
            'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
        ])
        ->firstOrFail();
    $validServicesCount = $mp->services()
        ->whereNotNull('name')
        ->where('name', '<>', '')
        ->whereNotNull('daily_price')
        ->where('daily_price', '>', 0)
        ->count();
    $hasServices = $validServicesCount > 0;
    $balance = (float) ($mp->wallet_balance ?? 0);
    $balanceFormatted = IndonesianNumber::formatThousands((string) (int) round($balance));

    $monthParam = request()->query('month');
    $calendarData = MuthowifDashboardCalendarDataBuilder::build(
        $mp,
        is_string($monthParam) ? $monthParam : null
    );

    $upcomingBookings = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->limit(3)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
    $upcomingBookings->load('customer:id,name');

    $weekStart = now()->startOfWeek(Carbon::MONDAY);
    $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
    $weeklySchedule = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
        ->whereDate('starts_on', '<=', $weekEnd->toDateString())
        ->whereDate('ends_on', '>=', $weekStart->toDateString())
        ->orderBy('starts_on')
        ->limit(12)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
    $weeklySchedule->load('customer:id,name');

    $avgRating = $mp->average_rating !== null ? round((float) $mp->average_rating, 1) : null;
    $reviewsCount = (int) ($mp->booking_reviews_count ?? 0);

    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null && is_dir(public_path('images/bg-welcome'))) {
        foreach (array_diff(scandir(public_path('images/bg-welcome')) ?: [], ['.', '..']) as $n) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $n)) {
                $welcomeHeroBg = asset('images/bg-welcome/'.$n);
                break;
            }
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/welcome-hero.jpg'))
            ? asset('images/welcome-hero.jpg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $userInitial = mb_strtoupper(mb_substr(Auth::user()->name, 0, 1));

    $nextBooking = $upcomingBookings->first();
    $activeBookingsCount = (int) $mp->confirmed_bookings_count + (int) $mp->pending_bookings_count;

    $unreadChatCount = (int) BookingChatMessage::query()
        ->whereHas('muthowifBooking', fn ($q) => $q->where('muthowif_profile_id', $mp->getKey()))
        ->where('user_id', '!=', Auth::id())
        ->whereNull('read_at')
        ->count();

    $recentActivities = collect();
    $mp->bookings()
        ->with('customer:id,name')
        ->orderByDesc('updated_at')
        ->limit(5)
        ->get(['id', 'status', 'customer_id', 'updated_at'])
        ->each(function ($booking) use ($recentActivities) {
            $guest = $booking->customer?->name ?? __('dashboard_muthowif.guest');
            $text = match ($booking->status) {
                BookingStatus::Confirmed => __('dashboard_muthowif.activity_confirmed', ['name' => $guest]),
                BookingStatus::Pending => __('dashboard_muthowif.activity_pending', ['name' => $guest]),
                default => __('dashboard_muthowif.activity_updated', ['name' => $guest]),
            };
            $recentActivities->push(['text' => $text, 'time' => $booking->updated_at, 'kind' => 'booking']);
        });

    BookingChatMessage::query()
        ->whereHas('muthowifBooking', fn ($q) => $q->where('muthowif_profile_id', $mp->getKey()))
        ->where('user_id', '!=', Auth::id())
        ->with(['muthowifBooking.customer:id,name'])
        ->latest()
        ->limit(3)
        ->get()
        ->each(function ($message) use ($recentActivities) {
            $guest = $message->muthowifBooking?->customer?->name ?? __('dashboard_muthowif.guest');
            $recentActivities->push([
                'text' => __('dashboard_muthowif.activity_chat', ['name' => $guest]),
                'time' => $message->created_at,
                'kind' => 'chat',
            ]);
        });

    $recentActivities = $recentActivities->sortByDesc(fn ($item) => $item['time']?->timestamp ?? 0)->take(6)->values();

@endphp

<div
    class="scroll-smooth"
    x-data="{ showServicePrompt: {{ $hasServices ? 'false' : 'true' }} }"
    x-init="if (showServicePrompt) { $nextTick(() => { $refs.serviceBtn?.focus(); }); }"
>
    <div
        x-show="showServicePrompt"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 py-6 sm:px-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-baytgo">Aksi penting</p>
                    <h2 class="mt-3 text-2xl font-bold text-slate-900">Lengkapi layanan muthowif Anda</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Karena Anda belum menambahkan layanan, kami sarankan untuk langsung atur layanan agar profil Anda siap menerima permintaan booking.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-200 bg-slate-100 p-2 text-slate-600 transition hover:bg-slate-200"
                    @click="showServicePrompt = false"
                    aria-label="Tutup pemberitahuan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.28 4.28a.75.75 0 011.06 0L10 8.94l4.66-4.66a.75.75 0 111.06 1.06L11.06 10l4.66 4.66a.75.75 0 11-1.06 1.06L10 11.06l-4.66 4.66a.75.75 0 01-1.06-1.06L8.94 10 4.28 5.34a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="space-y-2">
                    <p class="text-sm text-slate-700">Tambahkan layanan harian Anda sekarang sehingga daftar layanan bisa tampil ke jamaah dan muthowif bisa mulai menerima booking.</p>
                    <p class="text-xs text-slate-500">Anda bisa mengatur layanan group dan private secara terpisah di halaman layanan.</p>
                </div>
                <a
                    href="{{ route('muthowif.pelayanan.edit') }}"
                    x-ref="serviceBtn"
                    class="inline-flex items-center justify-center rounded-2xl bg-baytgo px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/15 transition hover:bg-baytgo-800"
                >
                    Atur layanan sekarang
                </a>
            </div>
        </div>
    </div>
    @include('partials.dashboard-muthowif-layout')
</div>
