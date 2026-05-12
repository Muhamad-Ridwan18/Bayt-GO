@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifBookingMuthowifRejectionKind;
    /** @var \App\Models\MuthowifBooking $booking */
    $compact = $compact ?? false;
@endphp
@if ($booking->status === BookingStatus::Pending)
    @php
        $selectClass = $compact
            ? 'h-10 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-900 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20'
            : 'h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20';
        $textareaClass = $compact
            ? 'min-h-[4rem] w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20'
            : 'min-h-[5rem] w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20';
        $labelClass = $compact ? 'text-[11px] font-medium text-slate-600' : 'text-xs font-medium text-slate-600';
    @endphp
    <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="{{ $compact ? 'space-y-2' : 'space-y-3' }}" onsubmit="return confirm(@json(__('muthowif.bookings.reject_confirm_with_reason')));">
        @csrf
        <div>
            <label class="block {{ $labelClass }}">{{ __('muthowif.bookings.reject_reason_label') }}</label>
            <select name="muthowif_rejection_kind" required class="mt-1 {{ $selectClass }}">
                @foreach (MuthowifBookingMuthowifRejectionKind::cases() as $k)
                    <option value="{{ $k->value }}" @selected(old('muthowif_rejection_kind') === $k->value)>{{ $k->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('muthowif_rejection_kind')" class="mt-1" />
        </div>
        <div>
            <label class="block {{ $labelClass }}">{{ __('muthowif.bookings.reject_note_label') }}</label>
            <textarea name="muthowif_rejection_note" rows="{{ $compact ? 2 : 3 }}" maxlength="2000" class="mt-1 {{ $textareaClass }}" placeholder="{{ __('muthowif.bookings.reject_note_placeholder') }}">{{ old('muthowif_rejection_note') }}</textarea>
            <x-input-error :messages="$errors->get('muthowif_rejection_note')" class="mt-1" />
        </div>
        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            {{ __('muthowif.bookings.reject') }}
        </button>
    </form>
@endif
