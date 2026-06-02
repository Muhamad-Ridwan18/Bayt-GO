@php
    use App\Enums\BookingIncidentCaseType;
    use App\Enums\BookingReplacementStatus;
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;

    $openIncident = $openIncident ?? null;
    $incomingReplacement = $incomingReplacement ?? null;
    $b = $booking;
@endphp

@if ($b->status === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    @if ($incomingReplacement && $incomingReplacement->status === BookingReplacementStatus::AwaitingMuthowifConfirm)
        <section class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
            <h3 class="text-sm font-bold text-violet-950">{{ __('incidents.replacement_offer_title') }}</h3>
            <p class="mt-1 text-xs text-violet-900">
                {{ __('incidents.admin.propose_replacement') }}
                — {{ $incomingReplacement->incident->muthowifBooking?->booking_code }}
            </p>
            @if (filled($incomingReplacement->admin_note))
                <p class="mt-2 text-sm text-violet-900">{{ $incomingReplacement->admin_note }}</p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('muthowif.bookings.replacements.confirm', [$b, $incomingReplacement]) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">
                        {{ __('incidents.muthowif_confirm_replacement') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('muthowif.bookings.replacements.decline', [$b, $incomingReplacement]) }}" class="space-y-2">
                    @csrf
                    <input type="text" name="note" class="w-full rounded-lg border-violet-200 text-xs" placeholder="Catatan (opsional)">
                    <button type="submit" class="rounded-xl border border-violet-300 bg-white px-4 py-2 text-sm font-semibold text-violet-900">
                        {{ __('incidents.muthowif_decline_replacement') }}
                    </button>
                </form>
            </div>
        </section>
    @endif

    @if ($openIncident?->replacement_recruitment_open && ! $incomingReplacement)
        <section class="rounded-2xl border border-violet-100 bg-violet-50/80 p-4 text-sm">
            <a href="{{ route('muthowif.replacements.opportunities') }}" class="font-semibold text-violet-800 hover:text-violet-950">
                {{ __('incidents.muthowif.browse_opportunities') }}
            </a>
        </section>
    @endif

    @if (! $openIncident && auth()->user()?->can('reportAsMuthowif', $b))
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900">{{ __('incidents.muthowif_report_title') }}</h3>
            <form method="POST" action="{{ route('muthowif.bookings.incident.report', $b) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <select name="case_type" required class="w-full rounded-xl border-slate-300 text-sm">
                    <option value="{{ BookingIncidentCaseType::MuthowifUnavailable->value }}">{{ BookingIncidentCaseType::MuthowifUnavailable->label() }}</option>
                    <option value="{{ BookingIncidentCaseType::ForceMajeure->value }}">{{ BookingIncidentCaseType::ForceMajeure->label() }}</option>
                </select>
                <textarea name="statement" required rows="3" maxlength="5000" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Jelaskan kondisi dan ketersediaan bukti"></textarea>
                <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-600">
                <button type="submit" class="rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                    {{ __('incidents.muthowif_report_submit') }}
                </button>
            </form>
        </section>
    @elseif ($openIncident)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            {{ __('incidents.status_banner_open') }}
            <span class="font-semibold">{{ $openIncident->case_type->label() }}</span>
        </section>
    @endif
@endif
