@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;
    use App\Support\BookingPostPayRules;

    $b = $booking;
    $st = $b->status;
@endphp

@if ($st === BookingStatus::Confirmed)
    <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 sm:p-6">
        <h2 class="text-base font-bold text-slate-900">{{ __('bookings.show.refund_reschedule_heading') }}</h2>
        <p class="mt-2 text-xs leading-relaxed text-slate-600">
            {!! __('bookings.show.refund_reschedule_intro_html', [
                'refund_days' => BookingPostPayRules::refundMinDaysBeforeService(),
                'reschedule_days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
            ]) !!}
        </p>

        <div class="mt-4 grid grid-cols-1 gap-3">
            @if ($b->isPaid())
                <a href="{{ route('bookings.refund', $b) }}" class="flex flex-col items-start gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                    <span class="text-sm font-bold text-slate-900">{{ __('bookings.show.process_refund') }}</span>
                    <span class="text-[10px] text-slate-500">{{ __('bookings.show.refund_card_hint') }}</span>
                </a>
                @if ($b->pendingRescheduleRequest())
                    <div class="flex flex-col items-start gap-1 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 ring-1 ring-amber-100/60">
                        <span class="text-sm font-bold text-amber-900">{{ __('bookings.show.reschedule_pending') }}</span>
                        <span class="text-[10px] text-amber-700">{{ __('bookings.show.reschedule_pending_hint') }}</span>
                    </div>
                @else
                    <a href="{{ route('bookings.reschedule', $b) }}" class="flex flex-col items-start gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                        <span class="text-sm font-bold text-slate-900">{{ __('bookings.show.submit_reschedule') }}</span>
                        <span class="text-[10px] text-slate-500">{{ __('bookings.show.reschedule_card_hint') }}</span>
                    </a>
                @endif
            @else
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 text-xs text-amber-800">
                    {{ __('bookings.reschedule_eligibility.not_paid') }}
                </div>
            @endif
        </div>

        @if ($b->refundRequests->isNotEmpty() || $b->rescheduleRequests->isNotEmpty())
            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-xs text-slate-600">
                <h3 class="font-bold uppercase tracking-wider text-[10px] text-slate-900">{{ __('bookings.show.change_request_history') }}</h3>
                @foreach ($b->refundRequests as $req)
                    <p class="rounded-lg bg-slate-50/80 px-3 py-2">
                        {{ __('bookings.show.timeline_refund', ['status' => $req->status->label(), 'datetime' => $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                        @if ($req->refund_bank_name || $req->refund_account_holder || $req->refund_account_number)
                            <br><span class="text-slate-600">{{ __('bookings.show.timeline_refund_bank', [
                                'bank' => $req->refund_bank_name ?: '—',
                                'holder' => $req->refund_account_holder ?: '—',
                                'number' => $req->refund_account_number ?: '—',
                            ]) }}</span>
                        @endif
                        @if ($req->customer_note)
                            <br><span class="text-slate-500">{{ __('bookings.show.timeline_refund_note', ['note' => $req->customer_note]) }}</span>
                        @endif
                    </p>
                @endforeach
                @foreach ($b->rescheduleRequests as $req)
                    <p class="rounded-lg bg-slate-50/80 px-3 py-2">
                        {{ __('bookings.show.timeline_reschedule', [
                            'status' => $req->status->label(),
                            'range' => \Carbon\Carbon::parse($req->new_starts_on)->format('d/m/Y').' – '.\Carbon\Carbon::parse($req->new_ends_on)->format('d/m/Y'),
                            'datetime' => $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                        ]) }}
                        @if ($req->muthowif_note)
                            <br><span class="text-slate-500">{{ __('bookings.show.timeline_muthowif_note', ['note' => $req->muthowif_note]) }}</span>
                        @endif
                    </p>
                @endforeach
            </div>
        @endif
    </section>
@endif

@if ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
    <section class="overflow-hidden rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50/90 to-white p-5 shadow-sm ring-1 ring-brand-200/50 sm:p-6">
        <h2 class="text-base font-bold text-slate-900">{{ __('bookings.show.complete_service_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('bookings.show.complete_service_intro') }}
        </p>

        <form method="POST" action="{{ route('bookings.complete', $b) }}" class="mt-5 space-y-4" onsubmit="return confirm(@json(__('bookings.show.complete_confirm')));">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.rating_required') }} <span class="text-red-600">*</span></label>
                <div class="flex flex-wrap gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50">
                            <input type="radio" name="rating" value="{{ $i }}" class="border-slate-300 text-brand-600 focus:ring-brand-500" @checked((int) old('rating', 5) === $i) required>
                            <span>{{ $i }} ★</span>
                        </label>
                    @endfor
                </div>
                @error('rating')
                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="complete_review" class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.review_optional') }}</label>
                <textarea id="complete_review" name="review" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder') }}">{{ old('review') }}</textarea>
                @error('review')
                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button class="w-full rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700">
                {{ __('bookings.show.complete_submit') }}
            </x-submit-button>
        </form>
    </section>
@endif
