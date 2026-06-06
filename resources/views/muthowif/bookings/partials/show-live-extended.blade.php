@php
    use App\Enums\BookingChangeRequestStatus;
    use App\Enums\BookingStatus;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
@endphp

<div class="min-w-0 ui-stack-compact lg:col-start-1">
    @include('bookings.partials.booking-documents', [
        'booking' => $b,
        'routeName' => 'muthowif.bookings.documents.show',
        'variant' => 'cards',
    ])

    @if ($b->refundRequests->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.refund_title') }}</h3>
            <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('muthowif.booking_show.refund_intro') }}</p>
            <ul class="mt-4 space-y-4 text-sm">
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
                        <p class="text-slate-700">{{ __('muthowif.booking_show.refund_net_prefix') }} <strong>Rp {{ $fmt((float) $req->net_refund_customer) }}</strong></p>
                        <p class="text-xs text-slate-600">{{ __('muthowif.booking_show.refund_fees', [
                            'platform' => $fmt((float) $req->refund_fee_platform),
                            'muthowif' => $fmt((float) $req->refund_fee_muthowif),
                        ]) }}</p>
                        @if ($req->refund_bank_name || $req->refund_account_holder || $req->refund_account_number)
                            <div class="rounded-lg border border-slate-100 bg-white px-3 py-2 text-xs text-slate-700">
                                <p class="font-semibold text-slate-800">{{ __('muthowif.booking_show.refund_bank_label') }}</p>
                                <p>{{ $req->refund_bank_name ?: '—' }} · {{ $req->refund_account_holder ?: '—' }}</p>
                                <p class="font-mono tabular-nums">{{ $req->refund_account_number ?: '—' }}</p>
                            </div>
                        @endif
                        @if ($req->customer_note)
                            <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_pilgrim') }}</span> {{ $req->customer_note }}</p>
                        @endif
                        @if ($req->muthowif_note)
                            <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_muthowif') }}</span> {{ $req->muthowif_note }}</p>
                        @endif
                        @if ($req->gateway_refunded_at)
                            <p class="text-xs text-emerald-800">{{ __('muthowif.booking_show.gateway_refund', ['datetime' => $req->gateway_refunded_at->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($b->rescheduleRequests->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.reschedule_title') }}</h3>
            <ul class="mt-4 space-y-4 text-sm">
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
                        @php
                            $prevStart = Carbon::parse($req->previous_starts_on)->format('d/m/Y');
                            $prevEnd = Carbon::parse($req->previous_ends_on)->format('d/m/Y');
                            $newStart = Carbon::parse($req->new_starts_on)->format('d/m/Y');
                            $newEnd = Carbon::parse($req->new_ends_on)->format('d/m/Y');
                        @endphp
                        <p class="text-slate-700">
                            {{ __('muthowif.booking_show.reschedule_from', ['old_start' => $prevStart, 'old_end' => $prevEnd]) }}
                            → <strong>{{ __('muthowif.booking_show.reschedule_to', ['new_start' => $newStart, 'new_end' => $newEnd]) }}</strong>
                        </p>
                        @if ($req->customer_note)
                            <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_pilgrim') }}</span> {{ $req->customer_note }}</p>
                        @endif
                        @if ($req->muthowif_note)
                            <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_muthowif') }}</span> {{ $req->muthowif_note }}</p>
                        @endif

                        @if ($req->isPending() && $st === BookingStatus::Confirmed && $b->isPaid())
                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:flex-wrap">
                                <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.approve', [$b, $req]) }}" class="min-w-0 flex-1 space-y-2 sm:min-w-[14rem]">
                                    @csrf
                                    <input type="text" name="muthowif_note" placeholder="{{ __('muthowif.booking_show.note_optional') }}" class="w-full rounded-lg border-slate-300 text-sm">
                                    <x-submit-button class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700" onclick="return confirm(@json(__('muthowif.booking_show.approve_reschedule_confirm')));">
                                        {{ __('muthowif.booking_show.approve_reschedule') }}
                                    </x-submit-button>
                                </form>
                                <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.reject', [$b, $req]) }}" class="min-w-0 flex-1 space-y-2 sm:min-w-[14rem]">
                                    @csrf
                                    <input type="text" name="muthowif_note" placeholder="{{ __('muthowif.booking_show.reject_reason_optional') }}" class="w-full rounded-lg border-slate-300 text-sm">
                                    <x-submit-button class="w-full rounded-lg border border-red-200 bg-white px-4 py-2 text-xs font-semibold text-red-800 hover:bg-red-50">
                                        {{ __('muthowif.booking_show.reject_short') }}
                                    </x-submit-button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
