@props([
    'hotelAvailable' => false,
    'transportAvailable' => false,
    'hotelPricePerDay' => 0,
    'transportPriceFlat' => 0,
    'oldWithSameHotel' => false,
    'oldWithTransport' => false,
    'accent' => 'brand',
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
    $hotelFmt = IndonesianNumber::formatThousands((string) (int) $hotelPricePerDay);
    $transportFmt = IndonesianNumber::formatThousands((string) (int) $transportPriceFlat);
    $hotelSelectedProvides = ! $oldWithSameHotel;
    $hotelSelectedNotProvides = $oldWithSameHotel && $hotelAvailable;
@endphp

<div class="space-y-5">
    <div class="space-y-2.5">
        <p class="text-sm font-semibold text-slate-800">{{ __('marketplace.panel.hotel_provides_question') }}</p>
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
        <p class="text-sm font-semibold text-slate-800">{{ __('marketplace.panel.transport_question') }}</p>
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
</div>
