@php
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $intent = $bookingIntent;
    $rangeLabel = null;
    if ($intent['start'] && $intent['end']) {
        $rangeLabel = Carbon::parse($intent['start'])->format('d/m/Y').' – '.Carbon::parse($intent['end'])->format('d/m/Y');
    }

    $pilgrimBounds = static function ($service): array {
        if (! $service) {
            return ['min' => 1, 'max' => 50];
        }
        $min = $service->min_pilgrims !== null ? (int) $service->min_pilgrims : 1;
        $max = $service->max_pilgrims !== null ? (int) $service->max_pilgrims : 50;
        $min = max(1, $min);
        if ($max < $min) {
            $max = $min;
        }

        return ['min' => $min, 'max' => $max];
    };

    $gBounds = $pilgrimBounds($group ?? null);
    $pBounds = $pilgrimBounds($private ?? null);
    $defaultService = $group ? 'group' : 'private';
    $defaultPilgrim = old('pilgrim_count', ($defaultService === 'private') ? $pBounds['min'] : $gBounds['min']);
    $selectedService = old('service_type', $defaultService);
    $oldWithSameHotel = old('with_same_hotel', false);
    $oldWithTransport = old('with_transport', false);
    $oldAddOnIds = collect(old('add_on_ids', []))->map(fn ($id) => (string) $id)->all();
@endphp

<section class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-market">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%2314b8a6\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M0 40L40 0H20L0 20M40 40V20L20 40\'/%3E%3C/g%3E%3C/svg%3E')] opacity-60 pointer-events-none"></div>

    <div class="relative bg-gradient-to-r from-slate-900 via-brand-900 to-amber-950 px-6 py-5 sm:px-8 sm:py-6 sm:flex sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-200/90">{{ __('marketplace.panel.checkout') }}</p>
            <h2 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">{{ __('marketplace.panel.title') }}</h2>
            <p class="mt-1 text-sm text-brand-100/90 max-w-xl">{{ __('marketplace.panel.subtitle') }}</p>
        </div>
        <div class="mt-4 sm:mt-0 shrink-0 flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2.5 ring-1 ring-white/20 backdrop-blur-sm">
            <svg class="h-5 w-5 text-amber-300 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
            </svg>
            <span class="text-sm font-medium text-white">BaytGo</span>
        </div>
    </div>

    <div class="relative bg-gradient-to-b from-slate-50/50 to-white px-6 py-6 sm:px-8 sm:py-8">
        @if ($intent['reason'] === 'guest')
            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                <p class="text-sm text-slate-700">{{ __('marketplace.panel.guest_intro') }}</p>
                <a href="{{ route('login.intended', ['next' => request()->getRequestUri()]) }}"
                   class="mt-4 inline-flex justify-center items-center px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold shadow-md hover:bg-brand-700">
                    {{ __('marketplace.panel.guest_login') }}
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-2 block text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('marketplace.panel.guest_register') }}</a>
                @endif
            </div>
        @elseif ($intent['reason'] === 'not_customer')
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                {!! __('marketplace.panel.not_customer') !!}
            </p>
        @elseif ($intent['reason'] === 'missing_dates')
            <p class="text-sm text-slate-700 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
                {!! __('marketplace.panel.missing_dates_html', ['link' => '<a href="'.e(route('layanan.index')).'" class="font-semibold text-brand-700 hover:text-brand-800">'.e(__('layanan.booking_panel_link')).'</a>']) !!}
            </p>
        @elseif ($intent['reason'] === 'invalid_dates')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                {{ __('marketplace.panel.invalid_dates') }}
            </p>
        @elseif ($intent['reason'] === 'past_start')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                {{ __('marketplace.panel.past_start') }}
            </p>
        @elseif ($intent['reason'] === 'range_too_long')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                {{ __('marketplace.panel.range_too_long') }}
            </p>
        @elseif ($intent['reason'] === 'slot_unavailable')
            <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                {!! __('marketplace.panel.slot_unavailable_html', [
                    'range' => $rangeLabel,
                    'link' => '<a href="'.e(route('layanan.index', array_filter(['start_date' => $startDate, 'end_date' => $endDate ?? null]))).'" class="font-semibold underline">'.e(__('layanan.booking_panel_link')).'</a>',
                ]) !!}
            </p>
        @elseif ($intent['can_submit'])
            @if (! $group && ! $private)
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                    {{ __('marketplace.panel.services_unconfigured') }}
                </p>
            @else
                {{-- Jangan pakai @json di dalam x-data="..." — tanda kutip JSON memutus atribut HTML dan merusak Alpine. --}}
                <div
                    class="space-y-6"
                    x-data="{
                        serviceType: '{{ old('service_type', $defaultService) }}',
                        bounds: {
                            group: { min: {{ (int) $gBounds['min'] }}, max: {{ (int) $gBounds['max'] }} },
                            private: { min: {{ (int) $pBounds['min'] }}, max: {{ (int) $pBounds['max'] }} },
                        },
                        currentBounds() {
                            return this.serviceType === 'group' ? this.bounds.group : this.bounds.private;
                        },
                    }"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.stay_period') }}</p>
                            <p class="mt-0.5 text-base font-semibold text-slate-900">{{ $rangeLabel }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-brand-50 text-brand-800 text-xs font-semibold px-3 py-1 ring-1 ring-brand-200/80">{{ __('marketplace.panel.slot_available') }}</span>
                    </div>

                    @if ($errors->any())
                        <ul class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <form id="booking-form-{{ $profile->id }}" method="POST" action="{{ route('bookings.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="muthowif_profile_id" value="{{ $profile->id }}">
                        <input type="hidden" name="start_date" value="{{ $intent['start'] }}">
                        <input type="hidden" name="end_date" value="{{ $intent['end'] }}">

                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-900">{{ __('marketplace.panel.choose_package') }}</legend>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @if ($group)
                                    <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-brand-300 hover:shadow-md has-[:checked]:border-brand-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-brand-50 has-[:checked]:to-white has-[:checked]:shadow-md">
                                        <input type="radio" name="service_type" value="group" class="sr-only peer"
                                               x-model="serviceType"
                                               @checked(old('service_type', $defaultService) === 'group')>
                                        <span class="flex flex-col gap-1">
                                            <span class="inline-flex w-fit rounded-lg bg-brand-100 text-brand-800 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5">Group</span>
                                            <span class="font-semibold text-slate-900">{{ __('marketplace.panel.group_label') }}</span>
                                            @if ($group->daily_price !== null)
                                                <span class="text-sm text-slate-600">{{ __('marketplace.panel.from_daily') }} <span class="font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}</span>{{ __('marketplace.panel.per_day') }}</span>
                                            @endif
                                        </span>
                                        <span class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:[&_svg]:opacity-100">
                                            <svg class="h-3 w-3 text-white opacity-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </span>
                                    </label>
                                @endif
                                @if ($private)
                                    <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-amber-300 hover:shadow-md has-[:checked]:border-amber-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-amber-50 has-[:checked]:to-white has-[:checked]:shadow-md">
                                        <input type="radio" name="service_type" value="private" class="sr-only peer"
                                               x-model="serviceType"
                                               @checked(old('service_type', $defaultService) === 'private')>
                                        <span class="flex flex-col gap-1 pr-8">
                                            <span class="inline-flex w-fit rounded-lg bg-amber-100 text-amber-900 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5">Private</span>
                                            <span class="font-semibold text-slate-900">{{ __('marketplace.panel.private_label') }}</span>
                                            @if ($private->daily_price !== null)
                                                <span class="text-sm text-slate-600">{{ __('marketplace.panel.from_daily') }} <span class="font-bold text-amber-800">Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}</span>{{ __('marketplace.panel.per_day') }}</span>
                                            @endif
                                        </span>
                                        <span class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:[&_svg]:opacity-100">
                                            <svg class="h-3 w-3 text-white opacity-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </span>
                                    </label>
                                @endif
                            </div>
                        </fieldset>

                        <div>
                            <label for="pilgrim_count" class="text-sm font-semibold text-slate-900">{{ __('marketplace.panel.pilgrim_count') }}</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <input type="number" name="pilgrim_count" id="pilgrim_count" required
                                       min="1"
                                       class="block w-28 rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-center py-2.5"
                                       value="{{ $defaultPilgrim }}"
                                       x-bind:min="currentBounds().min"
                                       x-bind:max="currentBounds().max">
                                <span class="text-sm text-slate-500">{{ __('common.people') }}</span>
                            </div>
                            @if ($group)
                                <p class="mt-2 text-xs text-slate-500" x-show="serviceType === 'group'">
                                    {{ __('marketplace.panel.group_quota', ['min' => $gBounds['min'], 'max' => $gBounds['max'], 'people' => __('common.people')]) }}
                                </p>
                            @endif
                            @if ($private)
                                <p class="mt-2 text-xs text-slate-500" x-show="serviceType === 'private'">
                                    {{ __('marketplace.panel.private_quota', ['min' => $pBounds['min'], 'max' => $pBounds['max'], 'people' => __('common.people')]) }}
                                </p>
                            @endif
                        </div>

                        @if ($group)
                            <div x-show="serviceType === 'group'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                                <h3 class="text-sm font-bold text-slate-900">{{ __('marketplace.panel.addons_group') }}</h3>

                                @php
                                    $groupHotelAvailable = ($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0;
                                    $groupTransportAvailable = ($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0;
                                @endphp

                                <label class="flex items-start gap-3 {{ $groupHotelAvailable ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
                                    <input type="checkbox" name="with_same_hotel" value="1"
                                        class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                        x-bind:disabled="serviceType !== 'group'"
                                        @disabled(! $groupHotelAvailable)
                                        @checked($groupHotelAvailable && $oldWithSameHotel)>
                                    <span class="text-sm text-slate-700">
                                        @if ($groupHotelAvailable)
                                            {{ __('marketplace.panel.same_hotel_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $group->same_hotel_price_per_day)]) }}
                                        @else
                                            {{ __('marketplace.panel.same_hotel_no') }}
                                        @endif
                                    </span>
                                </label>

                                <label class="flex items-start gap-3 {{ $groupTransportAvailable ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
                                    <input type="checkbox" name="with_transport" value="1"
                                        class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                        x-bind:disabled="serviceType !== 'group'"
                                        @disabled(! $groupTransportAvailable)
                                        @checked($groupTransportAvailable && $oldWithTransport)>
                                    <span class="text-sm text-slate-700">
                                        @if ($groupTransportAvailable)
                                            {{ __('marketplace.panel.transport_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $group->transport_price_flat)]) }}
                                        @else
                                            {{ __('marketplace.panel.transport_no') }}
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endif

                        @if ($private)
                            <div x-show="serviceType === 'private'" class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm space-y-3">
                                <h3 class="text-sm font-bold text-slate-900">{{ __('marketplace.panel.addons_private') }}</h3>
                                @php
                                    $privateHotelAvailable = ($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0;
                                    $privateTransportAvailable = ($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0;
                                @endphp

                                @if ($private->addOns->isNotEmpty())
                                    @foreach ($private->addOns as $addon)
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="add_on_ids[]" value="{{ $addon->id }}"
                                                class="mt-1 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                                x-bind:disabled="serviceType !== 'private'"
                                                @checked(in_array((string) $addon->id, $oldAddOnIds, true))>
                                            <span class="text-sm text-slate-700">
                                                {{ $addon->name }}
                                                <span class="font-semibold text-amber-800">(+Rp {{ IndonesianNumber::formatThousands((string) (int) $addon->price) }})</span>
                                            </span>
                                        </label>
                                    @endforeach
                                @else
                                    <p class="text-sm text-slate-600">{{ __('marketplace.panel.no_private_addons') }}</p>
                                @endif

                                <label class="flex items-start gap-3 {{ $privateHotelAvailable ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
                                    <input type="checkbox" name="with_same_hotel" value="1"
                                        class="mt-1 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                        x-bind:disabled="serviceType !== 'private'"
                                        @disabled(! $privateHotelAvailable)
                                        @checked($privateHotelAvailable && $oldWithSameHotel)>
                                    <span class="text-sm text-slate-700">
                                        @if ($privateHotelAvailable)
                                            {{ __('marketplace.panel.same_hotel_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $private->same_hotel_price_per_day)]) }}
                                        @else
                                            {{ __('marketplace.panel.same_hotel_no') }}
                                        @endif
                                    </span>
                                </label>

                                <label class="flex items-start gap-3 {{ $privateTransportAvailable ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
                                    <input type="checkbox" name="with_transport" value="1"
                                        class="mt-1 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                        x-bind:disabled="serviceType !== 'private'"
                                        @disabled(! $privateTransportAvailable)
                                        @checked($privateTransportAvailable && $oldWithTransport)>
                                    <span class="text-sm text-slate-700">
                                        @if ($privateTransportAvailable)
                                            {{ __('marketplace.panel.transport_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $private->transport_price_flat)]) }}
                                        @else
                                            {{ __('marketplace.panel.transport_no') }}
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endif

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2 border-t border-slate-200">
                            <p class="text-xs text-slate-500 max-w-md">
                                {!! __('marketplace.panel.consent_html') !!}
                            </p>
                            <button type="submit"
                                    class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3.5 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 text-white text-sm font-bold shadow-lg shadow-brand-900/20 hover:from-brand-500 hover:to-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition">
                                {{ __('marketplace.panel.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </div>
</section>
