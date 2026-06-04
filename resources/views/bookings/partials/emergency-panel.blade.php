@php
    use App\Enums\BookingStatus;
    use App\Enums\EmergencyReportCaseType;
    use App\Enums\EmergencyReportStatus;
    use App\Enums\PaymentStatus;

    $b = $booking;
    $report = $activeEmergencyReport ?? null;
    $candidates = $selectableEmergencyOffers ?? collect();
@endphp

@if ($b->status === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    @if ($report)
        <section class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm">
            <p class="text-sm font-semibold text-amber-950">{{ __('emergency.customer.banner_title') }}</p>
            <p class="mt-1 text-xs text-amber-900/80">
                {{ $report->case_type->label() }} · {{ $report->status->label() }}
            </p>

            @if ($report->status === EmergencyReportStatus::Submitted || $report->status === EmergencyReportStatus::UnderReview)
                <p class="mt-3 text-xs text-amber-900">{{ __('emergency.customer.waiting_admin') }}</p>
            @endif

            @if ($candidates->isNotEmpty() && $b->emergency_replacement_at === null)
                <div class="mt-4 space-y-3">
                    <h3 class="text-sm font-bold text-slate-900">{{ __('emergency.customer.choice_title', ['count' => $candidates->count()]) }}</h3>
                    <p class="text-xs text-slate-600">{{ __('emergency.customer.choice_hint') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($candidates as $offer)
                            <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                                <p class="font-semibold text-slate-900">{{ $offer->muthowifProfile?->user?->name ?? '—' }}</p>
                                @if (filled($offer->muthowifProfile?->slug))
                                    <a href="{{ route('layanan.show', $offer->muthowifProfile) }}" class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:text-brand-800" target="_blank" rel="noopener">
                                        {{ __('emergency.customer.view_profile') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('bookings.emergency.select', [$b, $offer]) }}" class="mt-3" onsubmit="return confirm(@json(__('emergency.customer.select_confirm', ['name' => $offer->muthowifProfile?->user?->name ?? '—'])));">
                                    @csrf
                                    <x-submit-button class="w-full rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                                        {{ __('emergency.customer.select_button') }}
                                    </x-submit-button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($report->status === EmergencyReportStatus::Verified && $b->emergency_replacement_at === null)
                <p class="mt-3 text-xs text-amber-900">{{ __('emergency.customer.waiting_candidates') }}</p>
            @endif

            @if ($b->emergency_replacement_at !== null)
                <p class="mt-3 text-sm font-medium text-emerald-900">{{ __('emergency.customer.replacement_done', ['name' => $b->muthowifProfile?->user?->name ?? '—']) }}</p>
            @endif
        </section>
    @elseif (auth()->user()?->can('reportEmergency', $b))
        <section class="rounded-2xl border border-red-200 bg-red-50/80 p-5">
            <p class="text-sm font-semibold text-red-950">{{ __('emergency.customer.report_title') }}</p>
            <p class="text-xs text-red-800 mt-1">{{ __('emergency.customer.report_hint') }}</p>
            <form method="POST" action="{{ route('bookings.emergency.store', $b) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-slate-700">{{ __('emergency.customer.case_type_label') }}</label>
                    <select name="case_type" required class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        @foreach (EmergencyReportCaseType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <textarea name="description" rows="3" maxlength="5000" class="w-full rounded-xl border-red-200 text-sm" placeholder="{{ __('emergency.customer.description_placeholder') }}"></textarea>
                <div>
                    <label class="text-xs font-semibold text-slate-700">{{ __('emergency.customer.evidence_label') }}</label>
                    <input type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp" class="mt-1 w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-500">{{ __('emergency.customer.evidence_optional') }}</p>
                </div>
                <x-submit-button class="w-full rounded-xl bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-800 sm:w-auto" onclick="return confirm(@json(__('emergency.customer.report_confirm')));">
                    {{ __('emergency.customer.report_button') }}
                </x-submit-button>
            </form>
        </section>
    @endif
@endif
