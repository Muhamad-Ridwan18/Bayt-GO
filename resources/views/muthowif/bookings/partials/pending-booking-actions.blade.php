@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifBookingMuthowifRejectionKind;

    /** @var \App\Models\MuthowifBooking $booking */
    $variant = $variant ?? 'card';
    $rejectFormId = 'reject-booking-' . $booking->getKey();
    $noteMax = 250;
    $oldNote = (string) old('muthowif_rejection_note', '');
@endphp

@if ($booking->status === BookingStatus::Pending)
    <div x-data="{ submitting: false }" data-submit-lock-scope="off">
    @if ($variant === 'card')
        <div class="border-t border-slate-100 px-4 py-5 sm:px-5">
            <div class="grid gap-5 lg:grid-cols-5 lg:gap-6">
                {{-- Form penolakan --}}
                <div class="lg:col-span-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2a.75.75 0 01.673.418l3.5 7.5A.75.75 0 0113.5 11H11v5.75a.75.75 0 01-1.5 0V11H6.5a.75.75 0 01-.673-1.082l3.5-7.5A.75.75 0 0110 2zm-2.45 8.5h4.9L10 5.79 7.55 10.5z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.pending_actions') }}</h3>
                    </div>

                    <form
                        id="{{ $rejectFormId }}"
                        method="POST"
                        action="{{ route('muthowif.bookings.cancel', $booking) }}"
                        class="mt-4 space-y-4"
                        @submit="if (!confirm(@json(__('muthowif.bookings.reject_confirm_with_reason')))) { $event.preventDefault(); return }; submitting = true"
                    >
                        @csrf
                        <div>
                            <label for="{{ $rejectFormId }}-kind" class="block text-sm font-medium text-slate-700">
                                {{ __('muthowif.bookings.reject_reason_label') }}
                                <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <select
                                id="{{ $rejectFormId }}-kind"
                                name="muthowif_rejection_kind"
                                required
                                class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                            >
                                @foreach (MuthowifBookingMuthowifRejectionKind::cases() as $k)
                                    <option value="{{ $k->value }}" @selected(old('muthowif_rejection_kind') === $k->value)>{{ $k->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('muthowif_rejection_kind')" class="mt-1" />
                        </div>
                        <div>
                            <label for="{{ $rejectFormId }}-note" class="block text-sm font-medium text-slate-700">{{ __('muthowif.bookings.reject_note_label') }}</label>
                            <div class="relative mt-1.5">
                                <textarea
                                    id="{{ $rejectFormId }}-note"
                                    name="muthowif_rejection_note"
                                    rows="3"
                                    maxlength="{{ $noteMax }}"
                                    class="w-full resize-y rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                    placeholder="{{ __('muthowif.bookings.reject_note_placeholder') }}"
                                    x-model="rejectNote"
                                    @input="rejectNoteLen = $event.target.value.length"
                                >{{ $oldNote }}</textarea>
                                <span class="pointer-events-none absolute bottom-2 right-2 text-[11px] tabular-nums text-slate-400" x-text="rejectNoteLen + '/{{ $noteMax }}'"></span>
                            </div>
                            <x-input-error :messages="$errors->get('muthowif_rejection_note')" class="mt-1" />
                        </div>
                    </form>
                </div>

                {{-- Tips + tombol aksi --}}
                <div class="flex flex-col gap-4 lg:col-span-2">
                    <div class="rounded-xl border border-sky-100 bg-sky-50/80 p-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M11.983 1.907a.75.75 0 00-1.292-.657l-8.5 9.5A.75.75 0 002.75 12h6.876l-1.127 4.523a.75.75 0 001.292.657l8.5-9.5A.75.75 0 0017.25 8h-6.876l1.127-4.523z" />
                                </svg>
                            </span>
                            <p class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.tips_heading') }}</p>
                        </div>
                        <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-700">
                            @foreach (['tips_1', 'tips_2', 'tips_3'] as $tipKey)
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ __('muthowif.bookings.'.$tipKey) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" @submit="submitting = true">
                            @csrf
                            <button type="submit" :disabled="submitting" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 disabled:cursor-wait disabled:opacity-70">
                                <svg x-show="submitting" x-cloak class="h-4 w-4 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <svg x-show="!submitting" class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.approve_order'))"></span>
                            </button>
                        </form>
                        <button
                            type="submit"
                            form="{{ $rejectFormId }}"
                            :disabled="submitting"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border-2 border-red-200 bg-white px-4 text-sm font-bold text-red-700 transition hover:border-red-300 hover:bg-red-50 disabled:cursor-wait disabled:opacity-70"
                        >
                            <svg x-show="submitting" x-cloak class="h-4 w-4 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <svg x-show="!submitting" class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.reject_order'))"></span>
                        </button>
                        <p class="flex items-center justify-center gap-1.5 text-center text-[11px] text-slate-500">
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                            {{ __('muthowif.bookings.actions_audit') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
            <form
                id="{{ $rejectFormId }}"
                method="POST"
                action="{{ route('muthowif.bookings.cancel', $booking) }}"
                class="space-y-4 p-4 sm:p-5"
                @submit="if (!confirm(@json(__('muthowif.bookings.reject_confirm_with_reason')))) { $event.preventDefault(); return }; submitting = true"
            >
                @csrf
                <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                    <div>
                        <label for="{{ $rejectFormId }}-kind" class="block text-xs font-medium text-slate-600">{{ __('muthowif.bookings.reject_reason_label') }}</label>
                        <select id="{{ $rejectFormId }}-kind" name="muthowif_rejection_kind" required class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                            @foreach (MuthowifBookingMuthowifRejectionKind::cases() as $k)
                                <option value="{{ $k->value }}" @selected(old('muthowif_rejection_kind') === $k->value)>{{ $k->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('muthowif_rejection_kind')" class="mt-1" />
                    </div>
                    <div>
                        <label for="{{ $rejectFormId }}-note" class="block text-xs font-medium text-slate-600">{{ __('muthowif.bookings.reject_note_label') }}</label>
                        <textarea id="{{ $rejectFormId }}-note" name="muthowif_rejection_note" rows="2" maxlength="2000" class="mt-1 min-h-[2.5rem] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="{{ __('muthowif.bookings.reject_note_placeholder') }}">{{ $oldNote }}</textarea>
                        <x-input-error :messages="$errors->get('muthowif_rejection_note')" class="mt-1" />
                    </div>
                </div>
            </form>
            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:flex sm:justify-end sm:gap-3 sm:px-5">
                <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" class="min-w-0 sm:w-auto" @submit="submitting = true">
                    @csrf
                    <button type="submit" :disabled="submitting" class="inline-flex h-10 w-full min-w-0 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 disabled:cursor-wait disabled:opacity-70 sm:min-w-[9.5rem]">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg x-show="!submitting" class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.approve_order'))"></span>
                    </button>
                </form>
                <button type="submit" form="{{ $rejectFormId }}" :disabled="submitting" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-wait disabled:opacity-70 sm:min-w-[9.5rem] sm:w-auto">
                    <svg x-show="submitting" x-cloak class="h-4 w-4 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg x-show="!submitting" class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.reject_order'))"></span>
                </button>
            </div>
        </div>
    @endif
    </div>
@endif
