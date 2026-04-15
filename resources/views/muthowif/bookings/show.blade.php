@php
    use App\Enums\BookingChangeRequestStatus;
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('muthowif.bookings.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">← Kembali ke daftar</a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                @if (filled($b->booking_code))
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2 text-sm">
                        <p class="text-xs font-medium text-slate-500">Kode booking</p>
                        <p class="font-mono font-semibold tracking-tight text-slate-900">{{ $b->booking_code }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-slate-500">Jamaah</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $b->customer->name }}</p>
                    <p class="text-sm text-slate-600">{{ $b->customer->email }}</p>
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
                    @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                            PaymentStatus::Paid => 'bg-brand-50 text-brand-900 ring-brand-200',
                            PaymentStatus::Refunded => 'bg-slate-100 text-slate-700 ring-slate-200',
                            default => 'bg-orange-50 text-orange-900 ring-orange-200',
                        } }}">{{ $b->payment_status->label() }}</span>
                    @endif
                </div>
            </div>

            @if ($b->refundRequests->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h3 class="font-semibold text-slate-900">Riwayat refund</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Jamaah mengajukan refund di aplikasi; admin mentransfer nominal bersih ke jamaah.</p>
                    <ul class="space-y-4 text-sm">
                        @foreach ($b->refundRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-red-50 text-red-900 ring-red-200',
                                    } }}">{{ $req->status->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-slate-700">Net refund jamaah (estimasi): <strong>Rp {{ $fmt((float) $req->net_refund_customer) }}</strong></p>
                                <p class="text-xs text-slate-600">Potongan admin: platform Rp {{ $fmt((float) $req->refund_fee_platform) }}, muthowif Rp {{ $fmt((float) $req->refund_fee_muthowif) }} (dari harga dasar layanan).</p>
                                @if ($req->customer_note)
                                    <p class="text-slate-600"><span class="font-medium">Jamaah:</span> {{ $req->customer_note }}</p>
                                @endif
                                @if ($req->muthowif_note)
                                    <p class="text-slate-600"><span class="font-medium">Muthowif:</span> {{ $req->muthowif_note }}</p>
                                @endif
                                @if ($req->midtrans_refunded_at)
                                    <p class="text-xs text-emerald-800">Refund Midtrans dicatat {{ $req->midtrans_refunded_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($b->rescheduleRequests->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h3 class="font-semibold text-slate-900">Pengajuan reschedule</h3>
                    <ul class="space-y-4 text-sm">
                        @foreach ($b->rescheduleRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-red-50 text-red-900 ring-red-200',
                                    } }}">{{ $req->status->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-slate-700">
                                    Dari {{ Carbon::parse($req->previous_starts_on)->format('d/m/Y') }} – {{ Carbon::parse($req->previous_ends_on)->format('d/m/Y') }}
                                    → <strong>{{ Carbon::parse($req->new_starts_on)->format('d/m/Y') }} – {{ Carbon::parse($req->new_ends_on)->format('d/m/Y') }}</strong>
                                </p>
                                @if ($req->customer_note)
                                    <p class="text-slate-600"><span class="font-medium">Jamaah:</span> {{ $req->customer_note }}</p>
                                @endif
                                @if ($req->muthowif_note)
                                    <p class="text-slate-600"><span class="font-medium">Muthowif:</span> {{ $req->muthowif_note }}</p>
                                @endif

                                @if ($req->isPending() && $st === BookingStatus::Confirmed && $b->isPaid())
                                    <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-200">
                                        <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.approve', [$b, $req]) }}" class="space-y-2">
                                            @csrf
                                            <input type="text" name="muthowif_note" placeholder="Catatan (opsional)" class="w-full rounded-lg border-slate-300 text-sm">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700" onclick="return confirm('Setujui pergantian tanggal ini?');">
                                                Setujui reschedule
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.reject', [$b, $req]) }}" class="space-y-2">
                                            @csrf
                                            <input type="text" name="muthowif_note" placeholder="Alasan tolak (opsional)" class="w-full rounded-lg border-slate-300 text-sm">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-xs font-semibold text-red-800 hover:bg-red-50">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
