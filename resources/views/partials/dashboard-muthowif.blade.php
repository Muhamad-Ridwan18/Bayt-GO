@php
    use App\Enums\BookingStatus;
    use Carbon\Carbon;
    use App\Support\IndonesianNumber;

    $mp = Auth::user()->muthowifProfile;
    $mp->loadCount([
        'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
        'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
    ]);
    $balance = (float) ($mp->wallet_balance ?? 0);
    $balanceFormatted = IndonesianNumber::formatThousands((string) (int) round($balance));

    $calendarMonth = now()->startOfMonth();
    $calendarStart = $calendarMonth->copy()->startOfWeek(Carbon::MONDAY);
    $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

    $upcomingBookings = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->limit(8)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type']);
    $upcomingBookings->load('customer:id,name');

    $blockedDates = $mp->blockedDates()
        ->whereBetween('blocked_on', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
        ->orderBy('blocked_on')
        ->get(['id', 'blocked_on', 'note']);

    $blockedSet = $blockedDates
        ->pluck('blocked_on')
        ->map(fn ($date) => Carbon::parse($date)->toDateString())
        ->flip();

    $bookingSet = collect();
    $calendarDetails = [];
    foreach ($upcomingBookings as $bookingRow) {
        $cursor = Carbon::parse($bookingRow->starts_on)->startOfDay();
        $end = Carbon::parse($bookingRow->ends_on)->startOfDay();
        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            $bookingSet->put($dateKey, true);
            $calendarDetails[$dateKey]['bookings'] ??= [];
            $calendarDetails[$dateKey]['bookings'][] = [
                'name' => $bookingRow->customer?->name ?? 'Jamaah',
                'service' => $bookingRow->service_type?->label() ?? 'Layanan',
            ];
            $cursor->addDay();
        }
    }

    foreach ($blockedDates as $blockedRow) {
        $dateKey = Carbon::parse($blockedRow->blocked_on)->toDateString();
        $calendarDetails[$dateKey]['blocked'] ??= [];
        $calendarDetails[$dateKey]['blocked'][] = $blockedRow->note ?: 'Libur';
    }
@endphp

<div class="space-y-6">
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-emerald-950 to-brand-900 text-white shadow-market ring-1 ring-white/10">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
        <div class="absolute -right-20 top-0 h-64 w-64 rounded-full bg-emerald-400/15 blur-3xl pointer-events-none"></div>
        <div class="relative px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
            <div class="space-y-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <img
                        src="{{ route('layanan.photo', $mp) }}"
                        alt="Foto {{ Auth::user()->name }}"
                        class="mx-auto sm:mx-0 h-28 w-28 sm:h-32 sm:w-32 lg:h-36 lg:w-36 rounded-3xl object-cover border-2 border-white/30 bg-white/10 shadow-lg"
                        onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22%3E%3Crect fill=%22%230f172a%22 width=%22128%22 height=%22128%22/%3E%3Ctext x=%2250%25%22 y=%2255%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22%23ffffff%22%3E{{ mb_substr(Auth::user()->name, 0, 1) }}%3C/text%3E%3C/svg%3E'"
                    >
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium text-emerald-200/90">Selamat datang,</p>
                        <p class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">{{ Auth::user()->name }}</p>
                        <span class="mt-3 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-100 ring-1 ring-white/20">
                            Muthowif terverifikasi
                        </span>
                        <p class="mt-4 text-sm text-emerald-100/90 leading-relaxed">
                            Kelola layanan, jadwal, dan permintaan jamaah dari satu dasbor. Saldo mencerminkan dana yang siap dicairkan (diperbarui sesuai kebijakan pembayaran).
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">Bahasa</p>
                        <p class="mt-2 text-sm text-white/95 leading-relaxed">
                            {{ $mp->languagesForDisplay() !== [] ? implode(', ', $mp->languagesForDisplay()) : 'Belum diisi' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">Studi / pendidikan</p>
                        <p class="mt-2 text-sm text-white/95 leading-relaxed">
                            {{ $mp->educationsForDisplay() !== [] ? implode(', ', $mp->educationsForDisplay()) : 'Belum diisi' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">Pengalaman</p>
                        <p class="mt-2 text-sm text-white/95 leading-relaxed">
                            {{ $mp->workExperiencesForDisplay() !== [] ? implode(', ', $mp->workExperiencesForDisplay()) : 'Belum diisi' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Saldo + ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
        <div class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-600 to-emerald-800 p-5 text-white shadow-md sm:col-span-2 lg:col-span-3">
            <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-100/90">Saldo dompet</p>
            <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums tracking-tight">Rp {{ $balanceFormatted }}</p>
            <p class="mt-2 text-xs text-emerald-100/80 leading-relaxed">Pendapatan bersih yang tercatat untuk Anda. Penarikan mengikuti ketentuan BaytGo.</p>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 sm:col-span-1 lg:col-span-3">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Menunggu</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ $mp->pending_bookings_count }}</p>
                    <p class="text-xs text-slate-500">Permintaan baru</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 sm:col-span-1 lg:col-span-3">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 text-brand-800">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Terkonfirmasi</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ $mp->confirmed_bookings_count }}</p>
                    <p class="text-xs text-slate-500">Booking aktif</p>
                </div>
            </div>
        </div>
        <a href="{{ route('muthowif.bookings.index') }}" class="group flex flex-col justify-center rounded-2xl border border-dashed border-brand-300 bg-brand-50/50 p-5 shadow-sm transition hover:border-brand-400 hover:bg-brand-50 sm:col-span-2 lg:col-span-3">
            <span class="text-sm font-semibold text-brand-900">Permintaan booking</span>
            <span class="mt-1 text-xs text-brand-800/80">Tinjau &amp; tanggapi jamaah</span>
            <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka
                <svg class="h-4 w-4 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
            </span>
        </a>
    </div>

    {{-- Kalender ringkas --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 lg:col-span-7">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Kalender {{ $calendarMonth->translatedFormat('F Y') }}</h3>
                <div class="flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-brand-500"></span> Booking</span>
                    <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Libur</span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dow)
                    <div class="py-1">{{ $dow }}</div>
                @endforeach
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                @for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay())
                    @php
                        $dateKey = $day->toDateString();
                        $isCurrentMonth = $day->month === $calendarMonth->month;
                        $isToday = $day->isToday();
                        $hasBooking = $bookingSet->has($dateKey);
                        $isBlocked = $blockedSet->has($dateKey);
                        $bookingsOnDay = collect($calendarDetails[$dateKey]['bookings'] ?? [])->unique(fn ($row) => ($row['name'] ?? '').'|'.($row['service'] ?? ''))->values();
                        $blockedOnDay = collect($calendarDetails[$dateKey]['blocked'] ?? [])->unique()->values();
                        $dayCardClass = match (true) {
                            $hasBooking && $isBlocked => 'border-violet-200 bg-violet-100',
                            $hasBooking => 'border-brand-200 bg-brand-100',
                            $isBlocked => 'border-amber-200 bg-amber-100',
                            default => $isCurrentMonth ? 'border-slate-200 bg-white' : 'border-slate-100 bg-slate-50 text-slate-400',
                        };
                    @endphp
                    <div class="group relative min-h-16 rounded-lg border px-2 py-1.5 {{ $dayCardClass }}">
                        <div class="text-xs font-semibold {{ $isToday ? 'text-brand-700' : 'text-slate-700' }}">{{ $day->day }}</div>
                        @if ($hasBooking || $isBlocked)
                            <div class="mt-1 space-y-0.5">
                                @if ($hasBooking)
                                    <span class="inline-block rounded bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700">Booking</span>
                                @endif
                                @if ($isBlocked)
                                    <span class="inline-block rounded bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Libur</span>
                                @endif
                            </div>
                        @endif

                        @if (($hasBooking || $isBlocked) && $isCurrentMonth)
                            <div class="absolute left-1/2 top-full z-20 mt-1 hidden w-52 -translate-x-1/2 rounded-lg border border-slate-200 bg-white p-2 text-left shadow-lg group-hover:block group-focus-within:block">
                                <p class="text-[11px] font-semibold text-slate-900">{{ $day->translatedFormat('d M Y') }}</p>
                                @if ($bookingsOnDay->isNotEmpty())
                                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-brand-700">Booking</p>
                                    <ul class="text-[11px] text-slate-700">
                                        @foreach ($bookingsOnDay as $row)
                                            <li>• {{ $row['name'] }} ({{ $row['service'] }})</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($blockedOnDay->isNotEmpty())
                                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700">Libur</p>
                                    <ul class="text-[11px] text-slate-700">
                                        @foreach ($blockedOnDay as $note)
                                            <li>• {{ $note }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="mt-1 text-[10px] text-slate-400">Hover/klik tanggal untuk lihat detail</p>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <div class="space-y-5 lg:col-span-5">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h4 class="text-sm font-semibold text-slate-900">Booking mendatang</h4>
                @if ($upcomingBookings->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada booking aktif.</p>
                @else
                    <ul class="mt-3 space-y-2.5 text-sm">
                        @foreach ($upcomingBookings as $row)
                            <li class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2">
                                <p class="font-medium text-slate-900">{{ $row->customer?->name ?? 'Jamaah' }}</p>
                                <p class="text-xs text-slate-600">
                                    {{ Carbon::parse($row->starts_on)->format('d/m') }} - {{ Carbon::parse($row->ends_on)->format('d/m') }} · {{ $row->status->label() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h4 class="text-sm font-semibold text-slate-900">Jadwal libur bulan ini</h4>
                @if ($blockedDates->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada tanggal libur di bulan ini.</p>
                @else
                    <ul class="mt-3 space-y-2.5 text-sm">
                        @foreach ($blockedDates as $row)
                            <li class="rounded-xl border border-amber-200 bg-amber-50/80 px-3 py-2">
                                <p class="font-medium text-slate-900">{{ Carbon::parse($row->blocked_on)->format('d M Y') }}</p>
                                <p class="text-xs text-slate-600">{{ $row->note ?: 'Libur' }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Aksi cepat --}}
    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <h3 class="text-base font-semibold text-slate-900">Aksi cepat</h3>
        <p class="mt-1 text-sm text-slate-600">Kelola bisnis Anda di marketplace.</p>
        <ul class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <li>
                <a href="{{ route('muthowif.pelayanan.edit') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-brand-200 hover:bg-white hover:shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-800" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                    </span>
                    <span class="min-w-0">Kelola pelayanan group &amp; private</span>
                </a>
            </li>
            <li>
                <a href="{{ route('muthowif.jadwal.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-brand-200 hover:bg-white hover:shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-900">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    </span>
                    <span class="min-w-0">Atur jadwal libur</span>
                </a>
            </li>
            <li>
                <a href="{{ route('muthowif.bookings.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-brand-200 hover:bg-white hover:shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 text-violet-800">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>
                    </span>
                    <span class="min-w-0">Permintaan booking jamaah</span>
                </a>
            </li>
            <li>
                <a href="{{ route('layanan.show', $mp) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-brand-200 hover:bg-white hover:shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-800">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                    </span>
                    <span class="min-w-0">Lihat halaman publik profil</span>
                </a>
            </li>
            <li>
                <a href="{{ route('muthowif.withdrawals.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-brand-200 hover:bg-white hover:shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-800">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1H4a2 2 0 00-2 2v8a2 2 0 002 2h1v1a2 2 0 002 2h8a2 2 0 002-2v-1h1a2 2 0 002-2V7a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H8zm2 4a1 1 0 100 2h4a1 1 0 100-2H10zM8 11a1 1 0 012 0h4a1 1 0 100-2H10a1 1 0 00-2 2z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span class="min-w-0">Tarik dana</span>
                </a>
            </li>
        </ul>
    </div>

</div>
