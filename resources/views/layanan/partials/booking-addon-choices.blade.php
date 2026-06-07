@props([
    'hotelAvailable' => false,
    'transportAvailable' => false,
    'hotelPricePerDay' => 0,
    'transportPriceFlat' => 0,
    'oldWithSameHotel' => false,
    'oldWithTransport' => false,
    'accent' => 'brand',
    'idSuffix' => '',
])

@php
    use App\Support\IndonesianNumber;

    $isAmber = $accent === 'amber';
    $labelChecked = $isAmber
        ? 'has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/40'
        : 'has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50/40';
    $radioClass = $isAmber
        ? 'text-amber-600 focus:ring-amber-500'
        : 'text-brand-600 focus:ring-brand-500';
    $helpBtnClass = $isAmber
        ? 'border-amber-200 text-amber-700 hover:bg-amber-50'
        : 'border-brand-200 text-brand-700 hover:bg-brand-50';
    $hotelFmt = IndonesianNumber::formatThousands((string) (int) $hotelPricePerDay);
    $transportFmt = IndonesianNumber::formatThousands((string) (int) $transportPriceFlat);
    $hotelSelectedProvides = ! $oldWithSameHotel;
    $hotelSelectedNotProvides = $oldWithSameHotel && $hotelAvailable;
@endphp

<div class="space-y-5" x-data="{ hotelHelpOpen: false, transportHelpOpen: false }">
    <div class="space-y-2.5">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-slate-800">{{ __('marketplace.panel.hotel_provides_question') }}</p>
            <button
                type="button"
                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold leading-none transition {{ $helpBtnClass }}"
                @click="hotelHelpOpen = true"
                aria-label="{{ __('marketplace.panel.hotel_provides_help_aria') }}"
            >?</button>
        </div>
        @if ($hotelAvailable)
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-slate-300 {{ $labelChecked }}">
                    <input
                        type="radio"
                        name="with_same_hotel"
                        value="0"
                        class="mt-0.5 size-4 border-slate-300 shadow-sm {{ $radioClass }}"
                        @checked($hotelSelectedProvides)
                    />
                    <span class="text-sm leading-relaxed text-slate-700">{{ __('marketplace.panel.hotel_provides_yes') }}</span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-slate-300 {{ $labelChecked }}">
                    <input
                        type="radio"
                        name="with_same_hotel"
                        value="1"
                        class="mt-0.5 size-4 border-slate-300 shadow-sm {{ $radioClass }}"
                        @checked($hotelSelectedNotProvides)
                    />
                    <span class="text-sm leading-relaxed text-slate-700">{{ __('marketplace.panel.hotel_provides_no', ['amount' => $hotelFmt]) }}</span>
                </label>
            </div>
        @else
            <p class="text-sm text-slate-500">{{ __('marketplace.panel.hotel_provides_unavailable') }}</p>
        @endif
    </div>

    <div class="space-y-2.5">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-slate-800">{{ __('marketplace.panel.transport_question') }}</p>
            <button
                type="button"
                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold leading-none transition {{ $helpBtnClass }}"
                @click="transportHelpOpen = true"
                aria-label="{{ __('marketplace.panel.transport_help_aria') }}"
            >?</button>
        </div>
        @if ($transportAvailable)
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-slate-300 {{ $labelChecked }}">
                    <input
                        type="radio"
                        name="with_transport"
                        value="1"
                        class="mt-0.5 size-4 border-slate-300 shadow-sm {{ $radioClass }}"
                        @checked($oldWithTransport)
                    />
                    <span class="text-sm leading-relaxed text-slate-700">{{ __('marketplace.panel.transport_choice_yes', ['amount' => $transportFmt]) }}</span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-slate-300 {{ $labelChecked }}">
                    <input
                        type="radio"
                        name="with_transport"
                        value="0"
                        class="mt-0.5 size-4 border-slate-300 shadow-sm {{ $radioClass }}"
                        @checked(! $oldWithTransport)
                    />
                    <span class="text-sm leading-relaxed text-slate-700">{{ __('marketplace.panel.transport_choice_no') }}</span>
                </label>
            </div>
        @else
            <p class="text-sm text-slate-500">{{ __('marketplace.panel.transport_unavailable_panel') }}</p>
        @endif
    </div>

    <div
        x-show="hotelHelpOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="booking-hotel-help-title-{{ $idSuffix }}"
        @keydown.escape.window="hotelHelpOpen = false"
    >
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="hotelHelpOpen = false"></div>
        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop>
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <h3 id="booking-hotel-help-title-{{ $idSuffix }}" class="text-base font-bold text-slate-900">{{ __('marketplace.panel.hotel_provides_help_title') }}</h3>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="hotelHelpOpen = false"
                    aria-label="{{ __('marketplace.panel.addon_help_close') }}"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm leading-relaxed text-slate-600">{{ __('marketplace.panel.hotel_provides_help_body') }}</p>
            </div>
            <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800" @click="hotelHelpOpen = false">
                    {{ __('marketplace.panel.addon_help_close') }}
                </button>
            </div>
        </div>
    </div>

    <div
        x-show="transportHelpOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="booking-transport-help-title-{{ $idSuffix }}"
        @keydown.escape.window="transportHelpOpen = false"
    >
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="transportHelpOpen = false"></div>
        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop>
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <h3 id="booking-transport-help-title-{{ $idSuffix }}" class="text-base font-bold text-slate-900">{{ __('marketplace.panel.transport_help_title') }}</h3>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="transportHelpOpen = false"
                    aria-label="{{ __('marketplace.panel.addon_help_close') }}"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm leading-relaxed text-slate-600">{{ __('marketplace.panel.transport_help_body') }}</p>
            </div>
            <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800" @click="transportHelpOpen = false">
                    {{ __('marketplace.panel.addon_help_close') }}
                </button>
            </div>
        </div>
    </div>
</div>
