@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\BookingPostPayRules;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $b->loadMissing(['muthowifProfile.services']);
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $daily = (float) ($b->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
    $baseSubtotal = (float) $nights * $daily;

    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah) {
        if (! empty($b->add_ons_snapshot)) {
            $addonLines = collect($b->add_ons_snapshot)->map(fn ($a) => (object) $a);
        } elseif (! empty($b->selected_add_on_ids)) {
            foreach ($b->selected_add_on_ids as $aid) {
                if (isset($addonsById[$aid])) {
                    $addonLines->push($addonsById[$aid]);
                }
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);

    $sameHotelPrice = (float) ($b->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
    $sameHotelLine = $b->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;

    $transportLine = (float) ($b->transport_price_snapshot ?? ($b->with_transport && $service ? (float) $service->transport_price_flat : 0.0));

    $baseTotal = (float) ($baseSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $review = $b->review;

    // Hitung Fee Platform & Total Tagihan (murni dalam USD)
    $split = \App\Support\PlatformFee::split($baseTotal);
    
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
@endphp

<div class="mb-6">
    <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#2D5A4C] hover:text-[#1e3b32] transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Pesanan
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    {{-- LEFT COLUMN --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Profile Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-6 items-start sm:items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center min-w-0 w-full">
                    <img src="{{ route('layanan.photo', $b->muthowifProfile) }}" alt="Photo" class="h-24 w-24 sm:h-24 sm:w-24 shrink-0 rounded-full object-cover ring-1 ring-slate-200/50 shadow-sm">
                    <div class="min-w-0 space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-bold text-slate-900">{{ $b->muthowifProfile->user->name }}</h1>
                            <span class="inline-flex items-center rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-[#2D5A4C]">Terkonfirmasi</span>
                        </div>
                        <p class="text-sm text-slate-600">{{ $b->service_type?->label() ?? '-' }}</p>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-xs text-slate-600 mt-2">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ Carbon::parse($b->starts_on)->translatedFormat('d M Y') }} - {{ Carbon::parse($b->ends_on)->translatedFormat('d M Y') }} ({{ $nights }} Hari)</span>
                            </div>
                        </div>
                        @if (filled($b->booking_code))
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            <span>Kode Pesanan: <span class="font-bold text-slate-900">{{ $b->booking_code }}</span></span>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col gap-3 w-full sm:w-auto shrink-0 mt-4 sm:mt-0">
                    <a href="{{ route('messages.show', $b->muthowifProfile->user) }}" class="inline-flex w-full sm:w-[130px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        Chat
                    </a>
                    <a href="{{ route('layanan.show', $b->muthowifProfile) }}" class="inline-flex w-full sm:w-[130px] items-center justify-center gap-2 rounded-xl bg-[#2D5A4C] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#23483c]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Lihat Profil
                    </a>
                </div>
            </div>
        </div>

        @include('bookings.partials.referral-network-alternatives', [
            'booking' => $b,
            'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
            'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        ])

        {{-- Payment Details Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 mb-6">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-500 border border-slate-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
                Rincian Pembayaran
            </h2>
            
            <dl class="space-y-4 text-xs sm:text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Tarif / hari</dt>
                    <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format($daily) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Jumlah hari</dt>
                    <dd class="font-bold text-slate-900">{{ $nights }} hari</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Subtotal layanan</dt>
                    <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format($baseSubtotal) }}</dd>
                </div>
                @if ($addonLines->isNotEmpty())
                    @foreach ($addonLines as $ad)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">+ {{ $ad->name }}</dt>
                            <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format((float) $ad->price) }}</dd>
                        </div>
                    @endforeach
                @endif
                @if ($sameHotelLine > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</dt>
                        <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format($sameHotelLine) }}</dd>
                    </div>
                @endif
                @if ($transportLine > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">{{ __('bookings.show.transport_label') }}</dt>
                        <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format($transportLine) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Biaya platform</dt>
                    <dd class="font-bold text-slate-900">{{ \App\Support\Currency::format($customerPlatformFee) }}</dd>
                </div>
                
                <div class="flex justify-between gap-4 border-t border-slate-100 pt-4 mt-2">
                    <dt class="text-base font-bold text-slate-900">Total dibayar (customer)</dt>
                    <dd class="text-xl font-bold text-[#2D5A4C]">{{ \App\Support\Currency::format($customerTotal) }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex items-start gap-2.5 rounded-xl bg-[#F4F9F7] p-3.5">
                <svg class="h-4 w-4 mt-0.5 shrink-0 text-[#2D5A4C]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-xs font-medium text-[#2D5A4C]">Total yang Anda bayarkan sudah termasuk biaya platform.</p>
            </div>

            @if ($b->isAwaitingPayment())
                <a href="{{ route('bookings.payment', $b) }}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-[#2D5A4C] px-4 py-3.5 text-sm font-bold tracking-wide text-white shadow-sm transition hover:bg-[#23483c]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    Bayar Sekarang &rarr;
                </a>
            @elseif ($b->paid_at)
                <a href="{{ route('bookings.invoice', $b) }}" target="_blank" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-600/30 bg-emerald-50 px-4 py-3.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Lihat Invoice
                </a>
            @endif
        </div>

        {{-- Refund & Reschedule (Accordion Style) --}}
        @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && $b->paid_at))
            <div x-data="{ open: false }" class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                <button @click="open = !open" class="flex w-full items-center justify-between p-5 text-left focus:outline-none">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-[#2D5A4C]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </span>
                        <span class="font-bold text-sm text-slate-900">Refund & Reschedule</span>
                    </div>
                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="open" class="px-5 pb-5" x-collapse>
                    @if ($b->isPaid())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                            <a href="{{ route('bookings.refund', $b) }}" class="flex flex-col items-start gap-1 rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                                <span class="text-xs font-bold text-slate-900">{{ __('bookings.show.process_refund') }}</span>
                                <span class="text-[10px] text-slate-500">Ajukan pengembalian dana jika batal.</span>
                            </a>
                            @if ($b->pendingRescheduleRequest())
                                <div class="flex flex-col items-start gap-1 rounded-xl border border-amber-100 bg-amber-50/50 p-4 ring-1 ring-amber-100/60">
                                    <span class="text-xs font-bold text-amber-900">{{ __('bookings.show.reschedule_pending') }}</span>
                                    <span class="text-[10px] text-amber-700">Menunggu keputusan muthowif.</span>
                                </div>
                            @else
                                <a href="{{ route('bookings.reschedule', $b) }}" class="flex flex-col items-start gap-1 rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                                    <span class="text-xs font-bold text-slate-900">{{ __('bookings.show.submit_reschedule') }}</span>
                                    <span class="text-[10px] text-slate-500">Ganti tanggal layanan (perlu persetujuan).</span>
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center rounded-xl bg-[#FFF9EB] p-3 border border-[#FDE6B5] mt-2">
                            <div class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 text-[#B87A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-xs font-medium text-[#7A5100]">Reschedule hanya setelah pembayaran lunas.</span>
                            </div>
                        </div>
                    @endif

                    @if ($b->refundRequests->isNotEmpty() || $b->rescheduleRequests->isNotEmpty())
                        <div class="space-y-3 border-t border-slate-100 pt-5 mt-5 text-[10px] sm:text-xs text-slate-600">
                            <h3 class="font-bold text-slate-900 uppercase tracking-wider text-[10px]">Riwayat Pengajuan</h3>
                            @foreach ($b->refundRequests as $req)
                                <p class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-100">
                                    <strong class="text-slate-800">{{ $req->status->label() }} Refund</strong> - {{ $req->created_at?->translatedFormat('d M Y, H:i') }}
                                </p>
                            @endforeach
                            @foreach ($b->rescheduleRequests as $req)
                                <p class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-100">
                                    <strong class="text-slate-800">{{ $req->status->label() }} Reschedule</strong> - {{ $req->created_at?->translatedFormat('d M Y, H:i') }}
                                </p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        {{-- Reviews --}}
        @if ($st === BookingStatus::Completed)
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900 mb-2">{{ __('bookings.show.completed_rating_heading') }}</h2>
                <p class="text-xs text-slate-600 mb-5">{{ __('bookings.show.completed_rating_intro') }}</p>

                <form method="POST" action="{{ route('bookings.review', $b) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-2 block text-xs font-medium text-slate-700">{{ __('bookings.show.rating_required') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only" @checked((int) old('rating', $review?->rating ?? 5) === $i)>
                                    <span>{{ $i }}</span>
                                    <span class="text-amber-400">★</span>
                                </label>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="review" class="mb-2 block text-xs font-medium text-slate-700">{{ __('bookings.show.review_label') }}</label>
                        <textarea id="review" name="review" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder_edit') }}">{{ old('review', $review?->review) }}</textarea>
                        @error('review')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#2D5A4C] px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#23483c]">
                        {{ $review ? __('bookings.show.update_review') : __('bookings.show.submit_review') }}
                    </button>
                </form>
            </div>
        @endif
        
        {{-- Completion Confirmation --}}
        @if ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
            <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50/90 to-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.show.complete_service_heading') }}</h2>
                <p class="mt-1 text-xs text-slate-600 mb-5">
                    {{ __('bookings.show.complete_service_intro') }}
                </p>

                <form method="POST" action="{{ route('bookings.complete', $b) }}" class="space-y-4" onsubmit="return confirm(@json(__('bookings.show.complete_confirm')));">
                    @csrf
                    <div>
                        <label class="mb-2 block text-xs font-medium text-slate-700">{{ __('bookings.show.rating_required') }} <span class="text-red-600">*</span></label>
                        <div class="flex flex-wrap gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only" @checked((int) old('rating', 5) === $i) required>
                                    <span>{{ $i }}</span>
                                    <span class="text-amber-400">★</span>
                                </label>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="complete_review" class="mb-2 block text-xs font-medium text-slate-700">{{ __('bookings.show.review_optional') }}</label>
                        <textarea id="complete_review" name="review" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder') }}">{{ old('review') }}</textarea>
                        @error('review')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        {{ __('bookings.show.complete_submit') }}
                    </button>
                </form>
            </div>
        @endif
        
        {{-- Cancellation --}}
        @if ($st === BookingStatus::Pending || $b->isAwaitingPayment())
            <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="rounded-2xl border border-red-200/80 bg-red-50/30 p-6 shadow-sm" onsubmit="return confirm(@json(__('bookings.show.cancel_booking_confirm')));">
                @csrf
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-red-900">{{ __('bookings.show.cancel_section_title') }}</p>
                        <p class="mt-0.5 text-[10px] text-red-800/80">{{ __('bookings.show.cancel_section_hint') }}</p>
                    </div>
                    <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        Batalkan
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- RIGHT COLUMN --}}
    <div class="space-y-6">
        
        {{-- Documents --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 mb-4">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-500 border border-slate-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                </span>
                Dokumen Perjalanan
            </h2>
            <div class="space-y-3">
                @php
                    $docs = [
                        ['type' => 'outbound', 'path' => $booking->ticket_outbound_path, 'label' => 'Tiket Pesawat (Berangkat)'],
                        ['type' => 'return', 'path' => $booking->ticket_return_path, 'label' => 'Tiket Pesawat (Pulang)'],
                        ['type' => 'passport', 'path' => $booking->passport_path, 'label' => 'Paspor'],
                        ['type' => 'itinerary', 'path' => $booking->itinerary_path, 'label' => 'Itinerary'],
                        ['type' => 'visa', 'path' => $booking->visa_path, 'label' => 'Visa'],
                    ];
                    $hasDocs = false;
                @endphp
                @foreach ($docs as $doc)
                    @if (filled($doc['path']))
                        @php $hasDocs = true; @endphp
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 rounded bg-red-600 px-1.5 py-1">
                                    <span class="text-[8px] font-bold text-white tracking-widest">PDF</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-700">{{ $doc['label'] }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Tersedia</p>
                                </div>
                            </div>
                            <a href="{{ route('bookings.documents.show', [$booking, $doc['type']]) }}" target="_blank" class="shrink-0 flex h-7 w-7 items-center justify-center rounded-lg bg-[#E8F3EF] text-[#2D5A4C] hover:bg-[#cde4db] transition">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            </a>
                        </div>
                    @endif
                @endforeach
                
                @if(! $hasDocs)
                    <p class="text-xs text-slate-500 italic p-2">Belum ada dokumen yang diunggah oleh Muthowif.</p>
                @endif
            </div>
            
            @if($hasDocs)
            <button type="button" onclick="alert('Fitur sedang dikembangkan.')" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#F4F9F7] py-2.5 text-xs font-bold tracking-wide text-[#2D5A4C] transition hover:bg-[#e2f1ec]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Download Semua
            </button>
            @endif
        </div>

        {{-- Status Timeline --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900 mb-6">Status Pemesanan</h2>
            
            <div class="relative space-y-6 before:absolute before:inset-0 before:ml-3 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-slate-200">
                {{-- Created --}}
                <div class="relative flex items-start gap-4">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#2D5A4C] ring-4 ring-white z-10">
                        <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-bold text-slate-900">Booking Dibuat</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $b->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                </div>

                {{-- Confirmed by Muthowif --}}
                <div class="relative flex items-start gap-4">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed]) || $b->isAwaitingPayment() ? 'bg-[#2D5A4C]' : 'bg-white border-2 border-slate-300' }} ring-4 ring-white z-10">
                        @if(in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed]) || $b->isAwaitingPayment())
                            <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @endif
                    </div>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-bold {{ in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed]) || $b->isAwaitingPayment() ? 'text-slate-900' : 'text-slate-500' }}">Muthowif Mengonfirmasi</p>
                        @if(in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed]) || $b->isAwaitingPayment())
                            <p class="text-[10px] text-slate-500 mt-0.5">Telah dikonfirmasi</p>
                        @endif
                    </div>
                </div>

                {{-- Payment --}}
                <div class="relative flex items-start gap-4">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $b->paid_at ? 'bg-[#2D5A4C]' : 'bg-white border-2 border-[#2D5A4C]' }} ring-4 ring-white z-10">
                        @if($b->paid_at)
                            <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @endif
                    </div>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-bold {{ $b->paid_at ? 'text-slate-900' : 'text-[#2D5A4C]' }}">{{ $b->paid_at ? 'Pembayaran Lunas' : 'Menunggu Pembayaran' }}</p>
                        @if(! $b->paid_at && $b->isAwaitingPayment())
                            <p class="mt-1.5 text-[10px] leading-relaxed text-slate-500 italic">Pesanan akan otomatis dibatalkan jika pembayaran tidak dilakukan.</p>
                        @elseif($b->paid_at)
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $b->paid_at->translatedFormat('d M Y, H:i') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Completed --}}
                <div class="relative flex items-start gap-4">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $st === BookingStatus::Completed ? 'bg-[#2D5A4C]' : 'bg-white border-2 border-slate-300' }} ring-4 ring-white z-10">
                        @if($st === BookingStatus::Completed)
                            <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @endif
                    </div>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-bold {{ $st === BookingStatus::Completed ? 'text-slate-900' : 'text-slate-500' }}">Layanan Selesai</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Help Support --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-500 border border-slate-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </span>
                Butuh Bantuan?
            </h2>
            <p class="mt-2.5 text-xs text-slate-600">Hubungi tim kami melalui WhatsApp atau lihat FAQ.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="#" class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-[#2D5A4C] shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-[#2D5A4C]" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    WhatsApp
                </a>
                <a href="#" class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Lihat FAQ
                </a>
            </div>
        </div>
    </div>
</div>
