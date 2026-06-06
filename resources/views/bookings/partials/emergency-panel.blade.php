@php
    use App\Enums\BookingStatus;
    use App\Enums\EmergencyReportCaseType;
    use App\Enums\EmergencyReportStatus;
    use App\Enums\PaymentStatus;

    $b = $booking;
    $report = $activeEmergencyReport ?? null;
    $candidates = $selectableEmergencyOffers ?? collect();
    $previousMuthowif = $b->muthowifProfile?->user;
    $nights = $b->billingNightsInclusive();
@endphp

@if ($b->status === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    @if ($report)
        <section class="overflow-hidden rounded-2xl border border-amber-200/90 bg-gradient-to-b from-amber-50/95 to-white shadow-sm ring-1 ring-amber-100/70">
            <div class="border-b border-amber-100/80 px-5 py-4 sm:px-6">
                <p class="text-sm font-bold text-amber-950">{{ __('emergency.customer.banner_title') }}</p>
                <p class="mt-1 text-xs text-amber-900/85">
                    {{ $report->case_type->label() }} · {{ $report->status->label() }}
                </p>
                @if ($report->created_at)
                    <p class="mt-1 text-[11px] text-amber-800/80">
                        {{ __('emergency.customer.reported_at') }}: {{ $report->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} WIB
                    </p>
                @endif
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                <div class="rounded-xl border border-amber-200/60 bg-white/80 px-4 py-3.5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('emergency.customer.your_report') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $report->case_type->label() }}</p>
                    @if (filled($report->description))
                        <p class="mt-2 text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ $report->description }}</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">{{ __('emergency.customer.no_description') }}</p>
                    @endif
                </div>

                <dl class="grid gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3.5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.customer.booking_period') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $b->starts_on?->format('d M Y') }} – {{ $b->ends_on?->format('d M Y') }}
                            <span class="text-slate-500">({{ __('emergency.customer.nights', ['count' => $nights]) }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.customer.booking_service') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $b->service_type?->label() }}
                            · {{ __('muthowif.bookings.pilgrim_count', ['count' => $b->pilgrim_count]) }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.customer.previous_muthowif') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $previousMuthowif?->name ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($report->status === EmergencyReportStatus::Submitted || $report->status === EmergencyReportStatus::UnderReview)
                    <p class="rounded-xl bg-amber-100/70 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200/50">
                        {{ __('emergency.customer.waiting_admin') }}
                    </p>
                @endif

                @if ($candidates->isNotEmpty() && $b->emergency_replacement_at === null)
                    <div class="space-y-4 border-t border-amber-100/80 pt-5">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ __('emergency.customer.choice_title', ['count' => $candidates->count()]) }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('emergency.customer.choice_hint') }}</p>
                        </div>
                        <div class="ui-stack-tight">
                            @foreach ($candidates as $offer)
                                @include('bookings.partials.emergency-candidate-card', ['offer' => $offer, 'booking' => $b])
                            @endforeach
                        </div>
                    </div>
                @elseif ($report->status === EmergencyReportStatus::Verified && $b->emergency_replacement_at === null)
                    <p class="rounded-xl bg-amber-100/70 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200/50">
                        {{ __('emergency.customer.waiting_candidates') }}
                    </p>
                @endif

                @if ($b->emergency_replacement_at !== null)
                    <div class="rounded-xl bg-emerald-50 px-4 py-4 ring-1 ring-emerald-200/60">
                        <p class="text-sm font-bold text-emerald-950">{{ __('emergency.customer.replacement_done_title') }}</p>
                        <p class="mt-1 text-sm text-emerald-900">{{ __('emergency.customer.replacement_done', ['name' => $b->muthowifProfile?->user?->name ?? '—']) }}</p>
                        <p class="mt-2 text-xs text-emerald-800/90">{{ __('emergency.customer.replacement_done_hint') }}</p>
                    </div>
                @endif
            </div>
        </section>
    @elseif (auth()->user()?->can('reportEmergency', $b))
        <section class="rounded-2xl border border-red-200 bg-gradient-to-b from-red-50/90 to-white p-5 shadow-sm sm:p-6">
            <p class="text-sm font-bold text-red-950">{{ __('emergency.customer.report_title') }}</p>
            <p class="mt-1 text-sm text-red-800/90">{{ __('emergency.customer.report_hint') }}</p>
            <form method="POST" action="{{ route('bookings.emergency.store', $b) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-slate-700">{{ __('emergency.customer.case_type_label') }}</label>
                    <select name="case_type" required class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-red-300 focus:ring-red-200/50">
                        @foreach (EmergencyReportCaseType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700">{{ __('emergency.customer.description_label') }}</label>
                    <textarea name="description" rows="4" maxlength="5000" class="mt-1.5 w-full rounded-xl border-red-200/80 text-sm shadow-sm focus:border-red-300 focus:ring-red-200/50" placeholder="{{ __('emergency.customer.description_placeholder') }}"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700">{{ __('emergency.customer.evidence_label') }}</label>
                    <input type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp" class="mt-1.5 w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-500">{{ __('emergency.customer.evidence_optional') }}</p>
                </div>
                <x-submit-button class="w-full rounded-xl bg-red-700 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-red-800 sm:w-auto" onclick="return confirm(@json(__('emergency.customer.report_confirm')));">
                    {{ __('emergency.customer.report_button') }}
                </x-submit-button>
            </form>
        </section>
    @endif
@endif
