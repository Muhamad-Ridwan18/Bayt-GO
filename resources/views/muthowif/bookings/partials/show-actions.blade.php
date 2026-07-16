@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;

    $b = $booking;
    $st = $b->status;
@endphp

@if ($st === BookingStatus::Pending)
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.actions_heading') }}</h2>
        <p class="mt-1 text-xs text-slate-600">{{ __('muthowif.booking_show.actions_pending_hint') }}</p>
        @include('muthowif.bookings.partials.pending-booking-actions', ['booking' => $b, 'variant' => 'inline'])
    </section>
@elseif ($b->canCompleteSupportWithCode())
    <section class="rounded-2xl border border-brand-200 bg-brand-50 p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-brand-950">{{ __('layanan_pendukung.muthowif_completion_heading') }}</h2>
        <p class="mt-1 text-xs text-brand-900/80">{{ __('layanan_pendukung.muthowif_completion_intro') }}</p>

        <form method="POST" action="{{ route('muthowif.bookings.support-completion.code', $b) }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="support_completion_code" class="mb-1.5 block text-xs font-medium text-brand-950">{{ __('layanan_pendukung.muthowif_completion_code_label') }}</label>
                <input
                    id="support_completion_code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="12"
                    required
                    value="{{ old('code') }}"
                    class="w-full rounded-lg border-brand-200 text-sm tracking-widest shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    placeholder="000000"
                >
                @error('code')
                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <x-submit-button class="h-9 w-full rounded-lg bg-emerald-600 px-3.5 text-xs font-semibold text-white transition hover:bg-emerald-700 sm:w-auto">
                {{ __('layanan_pendukung.muthowif_completion_submit') }}
            </x-submit-button>
        </form>

        <form method="POST" action="{{ route('muthowif.bookings.support-completion.resend-code', $b) }}" class="mt-3">
            @csrf
            <button type="submit" class="text-xs font-semibold text-brand-800 underline-offset-2 hover:underline">
                {{ __('layanan_pendukung.muthowif_completion_resend') }}
            </button>
        </form>
    </section>
@elseif ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Pending)
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-amber-950">{{ __('muthowif.booking_show.actions_heading') }}</h2>
        <p class="mt-1 text-xs text-amber-900/80">{{ __('muthowif.booking_show.awaiting_payment_hint') }}</p>
        <form method="POST" action="{{ route('muthowif.bookings.cancel', $b) }}" class="mt-4" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));">
            @csrf
            <x-submit-button class="h-9 w-full rounded-lg border border-red-200 bg-white px-3.5 text-xs font-semibold text-red-800 transition hover:bg-red-50 sm:w-auto">
                {{ __('muthowif.bookings.cancel_unpaid') }}
            </x-submit-button>
        </form>
    </section>
@endif

@if ($st === BookingStatus::Completed && $b->payment_status === PaymentStatus::Paid)
    <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {{ __('muthowif.bookings.completed_notice') }}
    </p>
@endif
