@php
    use App\Enums\MuthowifServiceType;
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

    $hintSvcRaw = request()->query('service_type');
    $hintSvc = is_string($hintSvcRaw) ? $hintSvcRaw : '';
    $serviceFromQuery = in_array($hintSvc, ['group', 'private'], true) ? $hintSvc : null;
    if ($serviceFromQuery === 'group' && $group) {
        $defaultService = 'group';
    } elseif ($serviceFromQuery === 'private' && $private) {
        $defaultService = 'private';
    } else {
        $defaultService = $group ? 'group' : 'private';
    }
    $selectedService = old('service_type', $defaultService);

    $boundsForSelected = $selectedService === 'private' ? $pBounds : $gBounds;
    $pilgrimRaw = request()->query('pilgrim_count');
    $pilgrimFromQuery = is_numeric($pilgrimRaw) ? (int) $pilgrimRaw : null;
    if ($pilgrimFromQuery !== null) {
        $pilgrimFromQuery = max($boundsForSelected['min'], min($boundsForSelected['max'], $pilgrimFromQuery));
    }
    $defaultPilgrim = old('pilgrim_count', $pilgrimFromQuery ?? (($selectedService === 'private') ? $pBounds['min'] : $gBounds['min']));

    $oldWithSameHotel = old('with_same_hotel', false);
    $oldWithTransport = old('with_transport', false);
    $oldAddOnIds = collect(old('add_on_ids', []))->map(fn ($id) => (string) $id)->all();
    $addonsDetailsOpen = $oldWithSameHotel || $oldWithTransport || count($oldAddOnIds) > 0;
@endphp

<section id="booking-panel" class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-market ring-1 ring-slate-100/80 touch-manipulation">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%2314b8a6\' fill-opacity=\'0.035\'%3E%3Cpath d=\'M0 40L40 0H20L0 20M40 40V20L20 40\'/%3E%3C/g%3E%3C/svg%3E')] pointer-events-none opacity-50"></div>

    <div class="relative bg-gradient-to-r from-slate-900 via-brand-900 to-amber-950 px-5 py-4 sm:px-8 sm:py-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('marketplace.panel.checkout') }}</p>
        <h2 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('marketplace.panel.title') }}</h2>
        <p class="mt-1.5 max-w-3xl text-sm leading-relaxed text-brand-100/90 sm:text-base">{{ __('marketplace.panel.subtitle') }}</p>
    </div>

    <div class="relative bg-gradient-to-b from-slate-50/50 to-white px-5 py-6 sm:px-8 sm:py-8">
        @if ($intent['reason'] === 'guest')
            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                <p class="text-sm text-slate-700">{{ __('marketplace.panel.guest_intro') }}</p>
                <a href="{{ route('login.intended', ['next' => request()->getRequestUri()]) }}"
                   class="mt-4 inline-flex w-full justify-center items-center px-5 py-3.5 rounded-xl bg-brand-600 text-white text-sm font-semibold shadow-md hover:bg-brand-700">
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
        @elseif ($intent['reason'] === 'jadwal_tidak_tersedia')
            <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                {!! __('marketplace.panel.jadwal_tidak_tersedia_html', [
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
                        serviceType: '{{ $selectedService }}',
                        bounds: {
                            group: { min: {{ (int) $gBounds['min'] }}, max: {{ (int) $gBounds['max'] }} },
                            private: { min: {{ (int) $pBounds['min'] }}, max: {{ (int) $pBounds['max'] }} },
                        },
                        tcOpen: false,
                        tcAgree: false,
                        currentBounds() {
                            return this.serviceType === 'group' ? this.bounds.group : this.bounds.private;
                        },
                        openBookingTc() {
                            if (! this.$refs.bookingForm.checkValidity()) {
                                this.$refs.bookingForm.reportValidity();
                                return;
                            }
                            this.tcAgree = false;
                            this.tcOpen = true;
                        },
                        submitAfterTc() {
                            if (! this.tcAgree) {
                                return;
                            }
                            this.tcOpen = false;
                            this.$nextTick(() => this.$refs.bookingForm.requestSubmit());
                        },
                    }"
                    @keydown.escape.window="if (tcOpen) tcOpen = false"
                >
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:px-5 sm:py-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.stay_period') }}</p>
                            <p class="mt-1 text-base font-semibold tabular-nums text-slate-900 sm:text-lg">{{ $rangeLabel }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/80">{{ __('marketplace.panel.jadwal_tersedia') }}</span>
                    </div>

                    @if ($errors->any())
                        <ul class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl px-3 py-2.5 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <form id="booking-form-{{ $profile->id }}" x-ref="bookingForm" method="POST" action="{{ route('bookings.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <input type="hidden" name="muthowif_profile_id" value="{{ $profile->id }}">
                        <input type="hidden" name="start_date" value="{{ $intent['start'] }}">
                        <input type="hidden" name="end_date" value="{{ $intent['end'] }}">

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(13rem,16rem)] lg:items-start lg:gap-8 xl:gap-10">
                        <fieldset class="min-w-0">
                            <legend class="text-base font-bold text-slate-900">{{ __('marketplace.panel.choose_package') }}</legend>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500 sm:text-sm">{{ __('marketplace.panel.choose_package_help') }}</p>
                            <div class="mt-4 grid grid-cols-1 gap-3 {{ ($group && $private) ? 'lg:grid-cols-2 lg:gap-4' : '' }}">
                                @if ($group)
                                    <label class="relative flex min-h-[5.5rem] cursor-pointer items-start rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition-all active:scale-[0.99] hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-brand-50 has-[:checked]:to-white has-[:checked]:shadow-md sm:p-5">
                                        <input type="radio" name="service_type" value="group" class="sr-only peer"
                                               x-model="serviceType"
                                               @checked($selectedService === 'group')>
                                        <span class="flex flex-col gap-1 pr-8">
                                            <span class="inline-flex w-fit rounded-lg bg-brand-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-800">{{ MuthowifServiceType::Group->label() }}</span>
                                            <span class="text-base font-bold text-slate-900">{{ __('marketplace.panel.group_label') }}</span>
                                            @if ($group->daily_price !== null)
                                                <span class="text-sm text-slate-600">{{ __('marketplace.panel.from_daily') }} <span class="font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}</span>{{ __('marketplace.panel.per_day') }}</span>
                                            @endif
                                        </span>
                                        <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:[&_svg]:opacity-100 sm:right-4 sm:top-4">
                                            <svg class="h-3 w-3 text-white opacity-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </span>
                                    </label>
                                @endif
                                @if ($private)
                                    <label class="relative flex min-h-[5.5rem] cursor-pointer items-start rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition-all active:scale-[0.99] hover:border-amber-300 has-[:checked]:border-amber-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-amber-50 has-[:checked]:to-white has-[:checked]:shadow-md sm:p-5">
                                        <input type="radio" name="service_type" value="private" class="sr-only peer"
                                               x-model="serviceType"
                                               @checked($selectedService === 'private')>
                                        <span class="flex flex-col gap-1 pr-8">
                                            <span class="inline-flex w-fit rounded-lg bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900">{{ MuthowifServiceType::PrivateJamaah->label() }}</span>
                                            <span class="text-base font-bold text-slate-900">{{ __('marketplace.panel.private_label') }}</span>
                                            @if ($private->daily_price !== null)
                                                <span class="text-sm text-slate-600">{{ __('marketplace.panel.from_daily') }} <span class="font-bold text-amber-800">Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}</span>{{ __('marketplace.panel.per_day') }}</span>
                                            @endif
                                        </span>
                                        <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:[&_svg]:opacity-100 sm:right-4 sm:top-4">
                                            <svg class="h-3 w-3 text-white opacity-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </span>
                                    </label>
                                @endif
                            </div>
                        </fieldset>

                        <div class="min-w-0 lg:rounded-2xl lg:border lg:border-slate-200/90 lg:bg-slate-50/40 lg:p-5 xl:p-6">
                            <label for="pilgrim_count" class="text-base font-bold text-slate-900">{{ __('marketplace.panel.pilgrim_count') }}</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <input type="number" name="pilgrim_count" id="pilgrim_count" required
                                       min="1"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       class="block w-[7rem] rounded-xl border-slate-300 py-3 text-center text-base font-semibold shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-[7.5rem]"
                                       value="{{ $defaultPilgrim }}"
                                       x-bind:min="currentBounds().min"
                                       x-bind:max="currentBounds().max">
                                <span class="text-sm text-slate-600">{{ __('common.people') }}</span>
                            </div>
                            @if ($group)
                                <p class="mt-2 text-xs leading-snug text-slate-500 sm:text-sm" x-show="serviceType === 'group'">
                                    {{ __('marketplace.panel.group_quota', ['min' => $gBounds['min'], 'max' => $gBounds['max'], 'people' => __('common.people')]) }}
                                </p>
                            @endif
                            @if ($private)
                                <p class="mt-2 text-xs leading-snug text-slate-500 sm:text-sm" x-show="serviceType === 'private'">
                                    {{ __('marketplace.panel.private_quota', ['min' => $pBounds['min'], 'max' => $pBounds['max'], 'people' => __('common.people')]) }}
                                </p>
                            @endif
                        </div>
                        </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 sm:p-6">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-base font-bold text-slate-900">{{ __('marketplace.panel.addons_toggle') }}</h3>
                                    <p class="text-xs text-slate-500">{{ __('marketplace.panel.addons_toggle_hint') }}</p>
                                </div>
                                <div class="mt-4 space-y-4 border-t border-slate-100 pt-4">
                                    @if ($group)
                                        <div x-show="serviceType === 'group'" class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/80 p-4 ring-1 ring-slate-100/60 sm:p-5">
                                            <p class="text-xs font-bold uppercase tracking-wide text-brand-800">{{ __('marketplace.panel.addons_group') }}</p>

                                            @php
                                                $groupHotelAvailable = ($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0;
                                                $groupTransportAvailable = ($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0;
                                            @endphp

                                            <label class="flex items-start gap-3 {{ $groupHotelAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" name="with_same_hotel" value="1"
                                                    class="mt-1 size-4 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                    x-bind:disabled="serviceType !== 'group'"
                                                    @disabled(! $groupHotelAvailable)
                                                    @checked($groupHotelAvailable && $oldWithSameHotel)>
                                                <span class="text-sm leading-relaxed text-slate-700">
                                                    @if ($groupHotelAvailable)
                                                        {{ __('marketplace.panel.same_hotel_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $group->same_hotel_price_per_day)]) }}
                                                    @else
                                                        {{ __('marketplace.panel.same_hotel_no') }}
                                                    @endif
                                                </span>
                                            </label>

                                            <label class="flex items-start gap-3 {{ $groupTransportAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" name="with_transport" value="1"
                                                    class="mt-1 size-4 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                    x-bind:disabled="serviceType !== 'group'"
                                                    @disabled(! $groupTransportAvailable)
                                                    @checked($groupTransportAvailable && $oldWithTransport)>
                                                <span class="text-sm leading-relaxed text-slate-700">
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
                                        <div x-show="serviceType === 'private'" class="space-y-3 rounded-xl border border-amber-100/90 bg-amber-50/50 p-4 ring-1 ring-amber-100/70 sm:p-5">
                                            <p class="text-xs font-bold uppercase tracking-wide text-amber-950">{{ __('marketplace.panel.addons_private') }}</p>
                                            @php
                                                $privateHotelAvailable = ($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0;
                                                $privateTransportAvailable = ($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0;
                                            @endphp

                                            @if ($private->addOns->isNotEmpty())
                                                @foreach ($private->addOns as $addon)
                                                    <label class="flex cursor-pointer items-start gap-3">
                                                        <input type="checkbox" name="add_on_ids[]" value="{{ $addon->id }}"
                                                            class="mt-1 size-4 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                                            x-bind:disabled="serviceType !== 'private'"
                                                            @checked(in_array((string) $addon->id, $oldAddOnIds, true))>
                                                        <span class="text-sm leading-relaxed text-slate-700">
                                                            {{ $addon->name }}
                                                            <span class="font-semibold text-amber-800">(+Rp {{ IndonesianNumber::formatThousands((string) (int) $addon->price) }})</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            @else
                                                <p class="text-xs text-slate-600">{{ __('marketplace.panel.no_private_addons') }}</p>
                                            @endif

                                            <label class="flex items-start gap-3 {{ $privateHotelAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" name="with_same_hotel" value="1"
                                                    class="mt-1 size-4 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                                    x-bind:disabled="serviceType !== 'private'"
                                                    @disabled(! $privateHotelAvailable)
                                                    @checked($privateHotelAvailable && $oldWithSameHotel)>
                                                <span class="text-sm leading-relaxed text-slate-700">
                                                    @if ($privateHotelAvailable)
                                                        {{ __('marketplace.panel.same_hotel_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $private->same_hotel_price_per_day)]) }}
                                                    @else
                                                        {{ __('marketplace.panel.same_hotel_no') }}
                                                    @endif
                                                </span>
                                            </label>

                                            <label class="flex items-start gap-3 {{ $privateTransportAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" name="with_transport" value="1"
                                                    class="mt-1 size-4 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                                    x-bind:disabled="serviceType !== 'private'"
                                                    @disabled(! $privateTransportAvailable)
                                                    @checked($privateTransportAvailable && $oldWithTransport)>
                                                <span class="text-sm leading-relaxed text-slate-700">
                                                    @if ($privateTransportAvailable)
                                                        {{ __('marketplace.panel.transport_yes', ['amount' => IndonesianNumber::formatThousands((string) (int) $private->transport_price_flat)]) }}
                                                    @else
                                                        {{ __('marketplace.panel.transport_no') }}
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        <fieldset class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 sm:p-6">
                            <legend class="px-1 text-base font-bold text-slate-900">{{ __('marketplace.panel.docs_heading') }}</legend>
                            <p class="mt-2 max-w-4xl px-1 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ __('marketplace.panel.docs_intro') }}</p>

                            <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <label for="ticket_outbound_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_ticket_outbound') }} <span class="text-red-600">*</span></label>
                                    <p class="mt-1 text-xs leading-snug text-slate-500 sm:text-sm">{{ __('marketplace.panel.doc_ticket_outbound_help') }}</p>

                                    @php $tempOutPath = old('temp_ticket_outbound_path', session('temp_ticket_outbound_path')); @endphp
                                    @if($tempOutPath)
                                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            <span class="truncate">Berkas terunggah: {{ old('temp_ticket_outbound_name', session('temp_ticket_outbound_name')) }}</span>
                                            <input type="hidden" name="temp_ticket_outbound_path" value="{{ $tempOutPath }}">
                                            <input type="hidden" name="temp_ticket_outbound_name" value="{{ old('temp_ticket_outbound_name', session('temp_ticket_outbound_name')) }}">
                                        </div>
                                    @endif

                                    <input id="ticket_outbound_{{ $profile->id }}" type="file" name="ticket_outbound" @if(!$tempOutPath) required @endif accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    @error('ticket_outbound')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="ticket_return_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_ticket_return') }} <span class="text-red-600">*</span></label>
                                    <p class="mt-1 text-xs leading-snug text-slate-500 sm:text-sm">{{ __('marketplace.panel.doc_ticket_return_help') }}</p>

                                    @php $tempRetPath = old('temp_ticket_return_path', session('temp_ticket_return_path')); @endphp
                                    @if($tempRetPath)
                                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            <span class="truncate">Berkas terunggah: {{ old('temp_ticket_return_name', session('temp_ticket_return_name')) }}</span>
                                            <input type="hidden" name="temp_ticket_return_path" value="{{ $tempRetPath }}">
                                            <input type="hidden" name="temp_ticket_return_name" value="{{ old('temp_ticket_return_name', session('temp_ticket_return_name')) }}">
                                        </div>
                                    @endif

                                    <input id="ticket_return_{{ $profile->id }}" type="file" name="ticket_return" @if(!$tempRetPath) required @endif accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    @error('ticket_return')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="passport_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_passport') }} <span class="text-red-600">*</span></label>
                                    <p class="mt-1 text-xs leading-snug text-slate-500 sm:text-sm">{{ __('marketplace.panel.doc_passport_help') }}</p>

                                    @php $tempPasPath = old('temp_passport_path', session('temp_passport_path')); @endphp
                                    @if($tempPasPath)
                                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            <span class="truncate">Berkas terunggah: {{ old('temp_passport_name', session('temp_passport_name')) }}</span>
                                            <input type="hidden" name="temp_passport_path" value="{{ $tempPasPath }}">
                                            <input type="hidden" name="temp_passport_name" value="{{ old('temp_passport_name', session('temp_passport_name')) }}">
                                        </div>
                                    @endif

                                    <input id="passport_{{ $profile->id }}" type="file" name="passport" @if(!$tempPasPath) required @endif accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    @error('passport')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="itinerary_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">
                                        {{ __('marketplace.panel.doc_itinerary') }}
                                        <span x-show="serviceType === 'group'" class="text-red-600">*</span>
                                        <span x-show="serviceType === 'private'" class="font-normal text-slate-400">({{ __('marketplace.panel.doc_optional') }})</span>
                                    </label>
                                    <p class="mt-1 text-xs leading-snug text-slate-500 sm:text-sm">{{ __('marketplace.panel.doc_itinerary_help') }}</p>

                                    @php $tempItiPath = old('temp_itinerary_path', session('temp_itinerary_path')); @endphp
                                    @if($tempItiPath)
                                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            <span class="truncate">Berkas terunggah: {{ old('temp_itinerary_name', session('temp_itinerary_name')) }}</span>
                                            <input type="hidden" name="temp_itinerary_path" value="{{ $tempItiPath }}">
                                            <input type="hidden" name="temp_itinerary_name" value="{{ old('temp_itinerary_name', session('temp_itinerary_name')) }}">
                                        </div>
                                    @endif

                                    <input id="itinerary_{{ $profile->id }}" type="file" name="itinerary" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           x-bind:required="serviceType === 'group' && !'{{ $tempItiPath }}'"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                    @error('itinerary')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="visa_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_visa') }} <span class="text-slate-400">({{ __('marketplace.panel.doc_optional') }})</span></label>
                                    
                                    @php $tempVisaPath = old('temp_visa_path', session('temp_visa_path')); @endphp
                                    @if($tempVisaPath)
                                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100 max-w-lg">
                                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            <span class="truncate">Berkas terunggah: {{ old('temp_visa_name', session('temp_visa_name')) }}</span>
                                            <input type="hidden" name="temp_visa_path" value="{{ $tempVisaPath }}">
                                            <input type="hidden" name="temp_visa_name" value="{{ old('temp_visa_name', session('temp_visa_name')) }}">
                                        </div>
                                    @endif

                                    <input id="visa_{{ $profile->id }}" type="file" name="visa" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full max-w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 sm:max-w-lg" />
                                    @error('visa')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                </div>
                        </fieldset>

                        <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                            <button type="button"
                                    @click="openBookingTc()"
                                    class="order-1 inline-flex w-full min-h-[3rem] items-center justify-center rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-brand-900/18 transition hover:from-brand-500 hover:to-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:order-2 sm:w-auto sm:min-w-[12rem]">
                                {{ __('marketplace.panel.submit') }}
                            </button>
                            <p class="order-2 max-w-xl text-center text-xs leading-relaxed text-slate-600 sm:order-1 sm:text-left sm:text-sm">
                                {!! __('marketplace.panel.consent_html') !!}
                            </p>
                        </div>
                    </form>

                    <div
                        x-show="tcOpen"
                        x-cloak
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="'tc-title-{{ $profile->id }}'"
                    >
                        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="tcOpen = false" aria-hidden="true"></div>
                        <div
                            class="relative z-10 flex max-h-[min(90vh,40rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/10 sm:rounded-3xl"
                            @click.stop
                        >
                            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:px-6">
                                <h3 id="tc-title-{{ $profile->id }}" class="text-base font-bold text-slate-900">{{ __('marketplace.panel.tc_title') }}</h3>
                            </div>
                            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 text-sm leading-relaxed text-slate-600 sm:px-6">
                                {!! __('marketplace.panel.tc_body_html') !!}
                                <p class="mt-4 border-t border-slate-100 pt-4">
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-brand-700 underline decoration-brand-700/40 underline-offset-2 hover:text-brand-800">{{ __('marketplace.panel.tc_full_text') }}</a>
                                </p>
                                <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 ring-1 ring-slate-100">
                                    <input type="checkbox" x-model="tcAgree" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                                    <span class="text-sm font-medium text-slate-800">{{ __('marketplace.panel.tc_checkbox') }}</span>
                                </label>
                            </div>
                            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:gap-3 sm:px-6">
                                <button type="button" @click="tcOpen = false" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 sm:w-auto sm:py-2.5">
                                    {{ __('marketplace.panel.tc_cancel') }}
                                </button>
                                <button
                                    type="button"
                                    @click="submitAfterTc()"
                                    :disabled="!tcAgree"
                                    :class="tcAgree ? 'bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600' : 'cursor-not-allowed bg-slate-300 text-slate-500'"
                                    class="inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-bold text-white shadow-md transition sm:w-auto sm:py-2.5"
                                >
                                    {{ __('marketplace.panel.tc_submit') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>
