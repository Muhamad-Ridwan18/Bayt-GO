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
    $daily = $service && $service->daily_price !== null ? (float) $service->daily_price : null;
    $baseSubtotal = $daily !== null ? $nights * $daily : 0.0;
    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah && ! empty($b->selected_add_on_ids)) {
        foreach ($b->selected_add_on_ids as $aid) {
            if (isset($addonsById[$aid])) {
                $addonLines->push($addonsById[$aid]);
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);
    $sameHotelLine = 0.0;
    if ($b->with_same_hotel && $service && $service->same_hotel_price_per_day !== null) {
        $sameHotelLine = $nights * (float) $service->same_hotel_price_per_day;
    }
    $transportLine = 0.0;
    if ($b->with_transport && $service && $service->transport_price_flat !== null) {
        $transportLine = (float) $service->transport_price_flat;
    }
    $baseTotal = $b->resolvedAmountDue();
    $split = \App\Support\PlatformFee::split((float) $baseTotal);
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $review = $b->review;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Detail booking
            </h2>
            <a href="{{ route('bookings.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                ← Kembali ke daftar
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                @if (filled($b->booking_code))
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2 text-sm">
                        <p class="text-xs font-medium text-slate-500">Kode booking</p>
                        <p class="font-mono font-semibold tracking-tight text-slate-900">{{ $b->booking_code }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-slate-500">Muthowif</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $b->muthowifProfile->user->name }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-500">Periode</p>
                        <p class="font-medium text-slate-900 mt-0.5">
                            {{ Carbon::parse($b->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($b->ends_on)->format('d/m/Y') }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">{{ $nights }} hari layanan</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Layanan</p>
                        <p class="font-medium text-slate-900 mt-0.5">{{ $b->service_type?->label() ?? '—' }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $b->pilgrim_count }} jemaah</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($st) {
                        BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200',
                        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                        BookingStatus::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                        BookingStatus::Cancelled => 'bg-slate-100 text-slate-700 ring-slate-200',
                    } }}">{{ $st->label() }}</span>
                    @if ($b->payment_status !== PaymentStatus::Pending)
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                            PaymentStatus::Paid => 'bg-brand-100 text-brand-900 ring-brand-200',
                            PaymentStatus::RefundPending => 'bg-amber-50 text-amber-900 ring-amber-200',
                            PaymentStatus::Refunded => 'bg-slate-100 text-slate-700 ring-slate-200',
                            default => 'bg-orange-50 text-orange-900 ring-orange-200',
                        } }}">
                            {{ $b->payment_status->label() }}
                        </span>
                    @endif
                </div>
            </div>

            @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && $b->paid_at))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Rincian pembayaran</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Tarif / hari</dt>
                            <dd class="font-medium text-slate-900 text-right">
                                @if ($daily !== null)
                                    Rp {{ $fmt($daily) }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Jumlah hari</dt>
                            <dd class="font-medium text-slate-900">{{ $nights }} hari</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">
                            <dt class="text-slate-600">Subtotal layanan</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($baseSubtotal) }}</dd>
                        </div>
                        @if ($addonLines->isNotEmpty())
                            @foreach ($addonLines as $ad)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">+ {{ $ad->name }}</dt>
                                    <dd class="font-medium text-slate-900">Rp {{ $fmt((float) $ad->price) }}</dd>
                                </div>
                            @endforeach
                        @endif
                        @if ($sameHotelLine > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">+ Hotel sama ({{ $nights }} hari)</dt>
                                <dd class="font-medium text-slate-900">Rp {{ $fmt($sameHotelLine) }}</dd>
                            </div>
                        @endif
                        @if ($transportLine > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">+ Transportasi</dt>
                                <dd class="font-medium text-slate-900">Rp {{ $fmt($transportLine) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">
                            <dt class="text-slate-600">Biaya platform</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base">
                            <dt class="font-semibold text-slate-900">Total dibayar (customer)</dt>
                            <dd class="font-bold text-brand-700">Rp {{ $fmt($customerTotal) }}</dd>
                        </div>
                        {{-- <div class="flex justify-between gap-4 pt-2 text-sm">
                            <dt class="text-slate-600">Diterima muthowif (net)</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($muthowifNet) }}</dd>
                        </div> --}}
                    </dl>

                    @if ($b->isAwaitingPayment())
                        @php $paymentQuery = request()->query('payment'); @endphp
                        @if ($paymentQuery === 'success')
                            <p class="mt-4 text-xs text-slate-600 leading-relaxed">
                                Terima kasih! Kami sedang menunggu konfirmasi pembayaran dari <strong>Midtrans</strong>.
                                Jika invoice sudah berstatus lunas, tombol <em>cetak invoice</em> akan muncul otomatis.
                            </p>
                        @else
                            <p class="mt-4 text-xs text-slate-600 leading-relaxed">
                                Total yang Anda bayarkan sudah termasuk biaya platform.
                            </p>
                        @endif

                        <a href="{{ route('bookings.payment', $b) }}" class="mt-4 inline-flex justify-center items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-brand-700">
                            Bayar
                        </a>
                    @elseif ($b->paid_at && ($b->isPaid() || $b->isRefundPending() || $b->isRefunded()))
                        <p class="mt-4 text-sm text-emerald-800">
                            Dibayar pada {{ $b->paid_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.
                        </p>
                        <a href="{{ route('bookings.invoice', $b) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
                            Cetak invoice
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif
                </div>
            @endif

            @if ($st === BookingStatus::Confirmed && $b->isPaid())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-semibold text-slate-900">Refund & reschedule</h3>
                        <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                            Refund: paling lambat <strong>H-{{ BookingPostPayRules::refundMinDaysBeforeService() }}</strong> sebelum tanggal mulai layanan. Biaya admin: <strong>2,5%</strong> + <strong>1%</strong> dari harga dasar layanan. Setelah Anda mengajukan, <strong>admin mentransfer secara manual</strong> nominal bersih ke rekening Anda.
                            Reschedule: <strong>H-{{ BookingPostPayRules::rescheduleMinDaysBeforeService() }}</strong>, jumlah hari sama, <strong>perlu persetujuan muthowif</strong>.
                        </p>
                    </div>

                    @if ($refundEligibilityError === null && $refundPreview)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm text-slate-700 space-y-2">
                            <p>Perkiraan dana kembali ke rekening Anda: <strong>Rp {{ $fmt((float) $refundPreview['net_refund_customer']) }}</strong> (setelah potongan admin).</p>
                            <p class="text-xs text-slate-600">Total dibayar Rp {{ $fmt((float) $refundPreview['customer_paid_amount']) }}, potongan platform Rp {{ $fmt((float) $refundPreview['refund_fee_platform']) }}, muthowif Rp {{ $fmt((float) $refundPreview['refund_fee_muthowif']) }}.</p>
                        </div>
                        <form method="POST" action="{{ route('bookings.refund_request.store', $b) }}" class="space-y-3" onsubmit="return confirm('Booking akan dibatalkan dan admin akan mentransfer refund (nominal bersih setelah potongan). Lanjutkan?');">
                            @csrf
                            <div>
                                <label for="refund_note" class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                                <textarea id="refund_note" name="customer_note" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm">{{ old('customer_note') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Proses refund
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-slate-600">{{ $refundEligibilityError }}</p>
                    @endif

                    @if ($b->pendingRescheduleRequest())
                        <div class="rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 text-sm text-amber-900">
                            Pengajuan reschedule Anda sedang menunggu keputusan muthowif.
                        </div>
                    @elseif ($rescheduleEligibilityError === null)
                        <form method="POST" action="{{ route('bookings.reschedule_request.store', $b) }}" class="space-y-3 border-t border-slate-100 pt-6">
                            @csrf
                            <p class="text-sm font-medium text-slate-800">Jadwal baru ({{ $nights }} hari, sama dengan sekarang)</p>
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                x-data="{
                                    nights: {{ $nights }},
                                    endLabel: '—',
                                    updateEnd() {
                                        const v = this.$refs.start?.value;
                                        if (!v) { this.endLabel = '—'; return; }
                                        const d = new Date(v + 'T12:00:00');
                                        d.setDate(d.getDate() + (this.nights - 1));
                                        this.endLabel = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                                    }
                                }"
                                x-init="$nextTick(() => updateEnd())"
                            >
                                <div>
                                    <label for="new_start_date" class="block text-xs font-medium text-slate-600 mb-1">Mulai</label>
                                    <input
                                        type="date"
                                        id="new_start_date"
                                        name="new_start_date"
                                        x-ref="start"
                                        value="{{ old('new_start_date') }}"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-sm"
                                        @input="updateEnd()"
                                        @change="updateEnd()"
                                    >
                                    @error('new_start_date')
                                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <p class="block text-xs font-medium text-slate-600 mb-1">Selesai (otomatis)</p>
                                    <div class="flex min-h-[38px] items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800" x-text="endLabel"></div>
                                    <p class="mt-1 text-[11px] text-slate-500">Mengikuti {{ $nights }} hari layanan seperti booking ini.</p>
                                </div>
                            </div>
                            <div>
                                <label for="reschedule_note" class="block text-xs font-medium text-slate-600 mb-1">Catatan (opsional)</label>
                                <textarea id="reschedule_note" name="reschedule_note" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm">{{ old('reschedule_note') }}</textarea>
                                @error('reschedule_note')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                                Ajukan reschedule
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-slate-600 border-t border-slate-100 pt-6">{{ $rescheduleEligibilityError }}</p>
                    @endif

                    @if ($b->refundRequests->isNotEmpty() || $b->rescheduleRequests->isNotEmpty())
                        <div class="border-t border-slate-100 pt-4 space-y-3 text-xs text-slate-600">
                            @foreach ($b->refundRequests as $req)
                                <p>
                                    Refund {{ $req->status->label() }} — {{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                    @if ($req->customer_note)
                                        <br><span class="text-slate-500">Catatan: {{ $req->customer_note }}</span>
                                    @endif
                                </p>
                            @endforeach
                            @foreach ($b->rescheduleRequests as $req)
                                <p>
                                    Reschedule {{ $req->status->label() }} —
                                    {{ \Carbon\Carbon::parse($req->new_starts_on)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($req->new_ends_on)->format('d/m/Y') }}
                                    (diajukan {{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }})
                                    @if ($req->muthowif_note)
                                        <br><span class="text-slate-500">Muthowif: {{ $req->muthowif_note }}</span>
                                    @endif
                                </p>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($st === BookingStatus::Cancelled && $b->isRefundPending())
                @php $pend = $b->pendingRefundRequest(); @endphp
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-6 shadow-sm text-sm text-amber-950">
                    <p class="font-semibold">Refund menunggu transfer admin</p>
                    <p class="mt-2 leading-relaxed">
                        Nominal transfer ke Anda (perkiraan): <strong>Rp {{ $pend ? $fmt((float) $pend->net_refund_customer) : '—' }}</strong>.
                        Admin akan mengirim ke rekening yang telah Anda gunakan atau koordinasi lewat kontak support.
                    </p>
                </div>
            @endif

            @if ($st === BookingStatus::Cancelled && $b->isRefunded())
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm text-sm text-slate-700">
                    Booking dibatalkan dan refund telah ditandai selesai oleh admin. Hubungi admin jika dana belum diterima.
                </div>
            @endif

            @if ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
                <div class="rounded-2xl border border-brand-200 bg-brand-50/40 p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Selesaikan layanan</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Untuk menyelesaikan layanan, beri rating terlebih dahulu. Review bersifat opsional.
                    </p>

                    <form method="POST" action="{{ route('bookings.complete', $b) }}" class="mt-4 space-y-4" onsubmit="return confirm('Yakin layanan sudah selesai?');">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Rating <span class="text-red-600">*</span></label>
                            <div class="flex flex-wrap gap-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="rating" value="{{ $i }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked((int) old('rating', 5) === $i) required>
                                        <span>{{ $i }} ★</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="complete_review" class="block text-sm font-medium text-slate-700 mb-2">Review (opsional)</label>
                            <textarea id="complete_review" name="review" rows="4" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Tulis pengalaman Anda (opsional)">{{ old('review') }}</textarea>
                            @error('review')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Selesaikan layanan
                        </button>
                    </form>
                </div>
            @endif

            @if ($st === BookingStatus::Pending || $b->isAwaitingPayment())
                <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="rounded-2xl border border-red-100 bg-red-50/50 p-4" onsubmit="return confirm('Batalkan booking ini?');">
                    @csrf
                    <p class="text-sm text-red-900 font-medium">Batalkan booking</p>
                    <p class="mt-1 text-xs text-red-800/90">Slot akan dibuka kembali untuk muthowif lain jika belum dibayar.</p>
                    <button type="submit" class="mt-3 text-sm font-semibold text-red-700 hover:text-red-900 underline">
                        Ya, batalkan
                    </button>
                </form>
            @endif

            @if ($st === BookingStatus::Completed)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Rating & review</h3>
                    <p class="mt-1 text-sm text-slate-600">Bagikan pengalaman Anda menggunakan layanan muthowif ini.</p>

                    <form method="POST" action="{{ route('bookings.review', $b) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Rating</label>
                            <div class="flex flex-wrap gap-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="rating" value="{{ $i }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked((int) old('rating', $review?->rating ?? 5) === $i)>
                                        <span>{{ $i }} ★</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="review" class="block text-sm font-medium text-slate-700 mb-2">Review (opsional)</label>
                            <textarea id="review" name="review" rows="4" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Ceritakan pengalaman Anda...">{{ old('review', $review?->review) }}</textarea>
                            @error('review')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ $review ? 'Perbarui review' : 'Kirim review' }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

@if ($b->isAwaitingPayment())
    <script>
        setInterval(() => {
            window.location.reload();
        }, 10000);
    </script>
@endif
