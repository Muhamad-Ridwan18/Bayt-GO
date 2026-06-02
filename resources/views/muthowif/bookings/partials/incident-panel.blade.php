@php
    use App\Enums\BookingIncidentCaseType;
    use App\Enums\BookingIncidentStatus;
    use App\Enums\BookingReplacementStatus;
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;
    use App\Support\IncidentEventLabel;

    $openIncident = $openIncident ?? null;
    $incomingReplacement = $incomingReplacement ?? null;
    $peerReplacementsAwaitingConfirm = $peerReplacementsAwaitingConfirm ?? collect();
    $customerChoicePool = $customerChoicePool ?? collect();
    $b = $booking;
    $ownsBooking = auth()->user()?->muthowifProfile
        && (string) $b->muthowif_profile_id === (string) auth()->user()->muthowifProfile->getKey();
@endphp

@if ($b->status === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    {{-- Hanya muthowif pengganti (bukan pemilik pesanan) yang melihat form konfirmasi --}}
    @if (! $ownsBooking && $incomingReplacement && $incomingReplacement->status === BookingReplacementStatus::AwaitingMuthowifConfirm)
        <ul class="space-y-4">
            @include('muthowif.bookings.partials.replacement-invite-card', [
                'replacement' => $incomingReplacement,
                'defaultOpen' => true,
            ])
        </ul>
    @endif

    @if ($ownsBooking && ! $openIncident && auth()->user()?->can('reportAsMuthowif', $b))
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
    @elseif ($ownsBooking && $openIncident)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p>{{ __('incidents.status_banner_open') }}</p>
            <p class="mt-2 text-base font-bold text-amber-950">
                {{ $openIncident->case_type->label() }}
                <span class="font-normal text-amber-800">·</span>
                {{ $openIncident->status->label() }}
            </p>
            <p class="mt-2 text-xs leading-relaxed text-amber-900/90">{{ __('incidents.muthowif.customer_picks_replacement') }}</p>

            @if ($openIncident->events->isNotEmpty())
                <h3 class="mt-4 text-xs font-bold uppercase tracking-wide text-amber-900">{{ __('incidents.timeline_title') }}</h3>
                <ul class="mt-2 space-y-2 text-xs text-amber-950">
                    @foreach ($openIncident->events->take(8) as $event)
                        <li class="rounded-lg bg-white/60 px-3 py-2">
                            <span class="font-mono text-[10px] text-amber-800">{{ $event->created_at?->timezone(config('app.timezone'))->format('d/m H:i') }}</span>
                            <span class="ml-2">{{ IncidentEventLabel::for($event->event_type) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($openIncident->status === BookingIncidentStatus::AwaitingReplacement)
                <p class="mt-2 text-xs leading-relaxed text-amber-900/90">{{ __('incidents.muthowif.own_incident_awaiting_replacement') }}</p>
            @endif

            @if ($peerReplacementsAwaitingConfirm->isNotEmpty())
                <ul class="mt-3 space-y-1 border-t border-amber-200/80 pt-3 text-xs text-amber-900">
                    <li class="font-semibold">{{ __('incidents.muthowif.owner_waiting_peer_confirm') }}</li>
                    @foreach ($peerReplacementsAwaitingConfirm as $peer)
                        <li>
                            {{ $peer->replacementProfile?->user?->name ?? '—' }}
                            <span class="text-amber-700">— {{ __('incidents.replacement_status.awaiting_muthowif_confirm') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($customerChoicePool->isNotEmpty())
                <div class="mt-4 border-t border-amber-200/80 pt-3">
                    <p class="text-xs font-semibold text-amber-950">{{ __('incidents.muthowif.customer_choice_pool_heading') }}</p>
                    <p class="mt-1 text-[11px] text-amber-900/90">{{ __('incidents.muthowif.customer_choice_pool_hint') }}</p>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($customerChoicePool as $candidate)
                            <li class="rounded-lg bg-white/70 px-3 py-2 text-xs font-medium text-slate-800">
                                {{ $candidate->replacementProfile?->user?->name ?? '—' }}
                                <span class="text-amber-700">· {{ $candidate->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    @endif
@endif
