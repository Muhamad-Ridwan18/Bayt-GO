@php
    use App\Support\BookingPostPayRules;
    use App\Support\IndonesianNumber;

    $b = $booking;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-slate-50">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-red-400/10 blur-3xl"></div>
            <div class="absolute -left-20 top-32 h-80 w-80 rounded-full bg-slate-400/10 blur-3xl"></div>
        </div>

        <x-page-container class="relative z-10 pb-16 pt-8">
            <a href="{{ route('bookings.show', $b) }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-800">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.show.back_to_detail') }}
            </a>

            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xl shadow-slate-900/5">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-8 sm:px-8">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('bookings.show.refund_reschedule_heading') }}</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        {!! __('bookings.show.refund_reschedule_intro_html', [
                            'refund_days' => BookingPostPayRules::refundMinDaysBeforeService(),
                            'reschedule_days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
                        ]) !!}
                    </p>
                </div>

                <div class="p-6 sm:p-8">
                    @if ($refundEligibilityError === null && $refundPreview)
                        <div class="mb-8 rounded-2xl border border-slate-100 bg-slate-50/90 p-4 text-sm text-slate-700 ring-1 ring-slate-100 space-y-3">
                            <p class="font-semibold text-slate-900">{!! __('bookings.show.refund_estimate_html', ['amount' => $fmt((float) $refundPreview['net_refund_customer'])]) !!}</p>
                            <div class="text-xs text-slate-600 space-y-1">
                                <p>{!! __('bookings.show.refund_breakdown_html', [
                                    'base' => $fmt((float) $refundPreview['service_base_amount']),
                                    'paid' => $fmt((float) $refundPreview['customer_paid_amount']),
                                    'platform' => $fmt((float) $refundPreview['refund_fee_platform']),
                                    'muthowif' => $fmt((float) $refundPreview['refund_fee_muthowif']),
                                ]) !!}</p>
                            </x-page-container>
                        </div>

                        <form method="POST" action="{{ route('bookings.refund_request.store', $b) }}" class="space-y-6" onsubmit="return confirm(@js(__('bookings.show.refund_confirm')));">
                            @csrf
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="refund_bank_name" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('bookings.show.refund_bank_name') }} <span class="text-red-500">*</span></label>
                                    <input id="refund_bank_name" type="text" name="refund_bank_name" value="{{ old('refund_bank_name') }}" required maxlength="100" autocomplete="off" class="w-full rounded-xl border-slate-300 bg-slate-50/30 py-3 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.refund_bank_name_placeholder') }}" />
                                    @error('refund_bank_name')
                                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="refund_account_holder" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('bookings.show.refund_account_holder') }} <span class="text-red-500">*</span></label>
                                    <input id="refund_account_holder" type="text" name="refund_account_holder" value="{{ old('refund_account_holder') }}" required maxlength="255" autocomplete="name" class="w-full rounded-xl border-slate-300 bg-slate-50/30 py-3 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.refund_account_holder_placeholder') }}" />
                                    @error('refund_account_holder')
                                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="refund_account_number" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('bookings.show.refund_account_number') }} <span class="text-red-500">*</span></label>
                                    <input id="refund_account_number" type="text" name="refund_account_number" value="{{ old('refund_account_number') }}" required maxlength="64" inputmode="numeric" autocomplete="off" class="w-full rounded-xl border-slate-300 bg-slate-50/30 py-3 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.refund_account_number_placeholder') }}" />
                                    @error('refund_account_number')
                                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label for="refund_note" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('bookings.show.note_optional') }}</label>
                                <textarea id="refund_note" name="customer_note" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-300 bg-slate-50/30 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder') }}">{{ old('customer_note') }}</textarea>
                            </div>
                            <div class="flex pt-2">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 sm:w-auto">
                                    {{ __('bookings.show.process_refund') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900">Tidak Eligible</h2>
                            <p class="mt-2 max-w-sm text-sm text-slate-600">{{ $refundEligibilityError }}</p>
                            <a href="{{ route('bookings.show', $b) }}" class="mt-8 text-sm font-bold text-brand-700 hover:text-brand-800">
                                {{ __('bookings.payment.back_to_detail') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
