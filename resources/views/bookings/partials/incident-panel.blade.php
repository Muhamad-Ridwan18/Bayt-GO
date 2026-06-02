@php
    use App\Enums\BookingIncidentOverlayStatus;
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;
    use App\Support\IncidentEventLabel;

    $openIncident = $openIncident ?? null;
    $selectableReplacements = $selectableReplacements ?? collect();
    $b = $booking;
    $isBookingCustomer = auth()->check()
        && auth()->user()->isCustomer()
        && (string) auth()->id() === (string) $b->customer_id;
@endphp

@if ($b->status === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    @if ($openIncident)
        <section class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm">
            <p class="text-sm font-semibold text-amber-950">{{ __('incidents.status_banner_open') }}</p>
            <p class="mt-1 text-xs text-amber-900/80">
                {{ $openIncident->case_type->label() }}
                · {{ $openIncident->status->label() }}
            </p>

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

            @if ($isBookingCustomer && $selectableReplacements->isNotEmpty())
                <div class="mt-4 space-y-3">
                    <h3 class="text-sm font-bold text-slate-900">{{ __('incidents.customer_choice_title', ['count' => $selectableReplacements->count()]) }}</h3>
                    <p class="text-xs text-slate-600">{{ __('incidents.customer_choice_hint') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($selectableReplacements as $candidate)
                            <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                                <p class="font-semibold text-slate-900">{{ $candidate->replacementProfile?->user?->name ?? '—' }}</p>
                                @if (filled($candidate->replacementProfile?->slug))
                                    <a href="{{ route('layanan.show', $candidate->replacementProfile) }}" class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:text-brand-800" target="_blank" rel="noopener">
                                        {{ __('incidents.view_profile') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('bookings.replacements.select', [$b, $candidate]) }}" class="mt-3" onsubmit="return confirm(@json(__('incidents.select_confirm', ['name' => $candidate->replacementProfile?->user?->name ?? '—'])));">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                                        {{ __('incidents.select_this_muthowif') }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($openIncident->customer_choice_opened_at === null)
                <p class="mt-3 text-xs text-amber-900">{{ __('incidents.waiting_muthowif_candidates') }}</p>
            @endif
        </section>
    @elseif ($b->incident_status !== BookingIncidentOverlayStatus::Open && auth()->user()?->can('reportEmergency', $b))
        <section class="rounded-2xl border border-red-200 bg-red-50/80 p-5">
            <p class="text-sm text-red-900">{{ __('incidents.emergency_hint') }}</p>
            <form method="POST" action="{{ route('bookings.emergency', $b) }}" class="mt-3 space-y-3" onsubmit="return confirm(@json(__('incidents.emergency_button')));">
                @csrf
                <textarea name="statement" rows="2" maxlength="5000" class="w-full rounded-xl border-red-200 text-sm" placeholder="{{ __('bookings.show.customer_note_placeholder') }}"></textarea>
                <button type="submit" class="w-full rounded-xl bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-800 sm:w-auto">
                    {{ __('incidents.emergency_button') }}
                </button>
            </form>
        </section>
    @endif
@endif
