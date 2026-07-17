@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $intent = $bookingIntent;
    $rangeLabel = null;
    $tripRangeDisplay = null;
    if ($intent['start'] && $intent['end']) {
        try {
            $rangeLabel = Carbon::parse($intent['start'])->format('d/m/Y').' – '.Carbon::parse($intent['end'])->format('d/m/Y');
            $tripRangeDisplay = Carbon::parse($intent['start'])->translatedFormat('d M Y').' – '.Carbon::parse($intent['end'])->translatedFormat('d M Y');
        } catch (\Throwable) {
            $rangeLabel = null;
            $tripRangeDisplay = null;
        }
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
    $docErrorFields = ['ticket_outbound', 'ticket_return', 'passport', 'itinerary', 'visa'];
    $initialBookingStep = $errors->hasAny($docErrorFields) ? 2 : 1;
    $profileUrl = $profileUrl ?? route('layanan.show', $profile);
    $tripRangeLabel = $rangeLabel ?? ($searchRangeLabel ?? null);
    $changeDatesUrl = $indexedUrl ?? route('layanan.index', array_filter([
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
    ], fn ($v) => filled($v)));
    $canSubmit = $canSubmit ?? false;
@endphp

<section id="booking-panel" class="ui-checkout-shell touch-manipulation overflow-hidden">
        @if ($intent['reason'] === 'guest')
        <div class="ui-panel-body">
            <x-ui.alert type="info">
                <p>{{ __('marketplace.panel.guest_intro') }}</p>
                <a href="{{ route('login.intended', ['next' => request()->getRequestUri()]) }}" class="ui-btn-primary mt-4 w-full">
                    {{ __('marketplace.panel.guest_login') }}
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-2 block text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('marketplace.panel.guest_register') }}</a>
                @endif
            </x-ui.alert>
        </div>
        @elseif ($intent['reason'] === 'not_customer')
        <div class="ui-panel-body">
            <x-ui.alert type="warning">{!! __('marketplace.panel.not_customer') !!}</x-ui.alert>
        </div>
        @elseif ($intent['reason'] === 'missing_dates')
        <div class="ui-panel-body">
            <x-ui.card pad="md" class="text-sm text-slate-700 shadow-none">
                {!! __('marketplace.panel.missing_dates_html', ['link' => '<a href="'.e(route('layanan.index')).'" class="font-semibold text-brand-700 hover:text-brand-800">'.e(__('layanan.booking_panel_link')).'</a>']) !!}
            </x-ui.card>
        </div>
        @elseif ($intent['reason'] === 'invalid_dates')
        <div class="ui-panel-body">
            <x-ui.alert type="error">{{ __('marketplace.panel.invalid_dates') }}</x-ui.alert>
        </div>
        @elseif ($intent['reason'] === 'past_start')
        <div class="ui-panel-body">
            <x-ui.alert type="error">{{ __('marketplace.panel.past_start') }}</x-ui.alert>
        </div>
        @elseif ($intent['reason'] === 'range_too_long')
        <div class="ui-panel-body">
            <x-ui.alert type="error">{{ __('marketplace.panel.range_too_long') }}</x-ui.alert>
        </div>
        @elseif ($intent['reason'] === 'jadwal_tidak_tersedia')
        <div class="ui-panel-body">
            <x-ui.alert type="warning">
                {!! __('marketplace.panel.jadwal_tidak_tersedia_html', [
                    'range' => $rangeLabel,
                    'link' => '<a href="'.e(route('layanan.index', array_filter(['start_date' => $startDate, 'end_date' => $endDate ?? null]))).'" class="font-semibold underline">'.e(__('layanan.booking_panel_link')).'</a>',
                ]) !!}
            </x-ui.alert>
        </div>
        @elseif ($intent['can_submit'])
            @if (! $group && ! $private)
        <div class="ui-panel-body">
                <x-ui.alert type="warning">{{ __('marketplace.panel.services_unconfigured') }}</x-ui.alert>
        </div>
            @else
                {{-- Jangan pakai @json di dalam x-data="..." — tanda kutip JSON memutus atribut HTML dan merusak Alpine. --}}
                @php
                    $docFieldState = static function (string $field) {
                        $path = old("temp_{$field}_path", session("temp_{$field}_path"));
                        $name = old("temp_{$field}_name", session("temp_{$field}_name"));

                        return [
                            'path' => is_string($path) && $path !== '' ? $path : '',
                            'name' => is_string($name) && $name !== '' ? $name : '',
                            'uploading' => false,
                            'error' => '',
                        ];
                    };

                    $bookingFormConfig = [
                        'initialStep' => $initialBookingStep,
                        'serviceType' => $selectedService,
                        'pilgrimCount' => (int) $defaultPilgrim,
                        'bounds' => [
                            'group' => ['min' => (int) $gBounds['min'], 'max' => (int) $gBounds['max']],
                            'private' => ['min' => (int) $pBounds['min'], 'max' => (int) $pBounds['max']],
                        ],
                        'labels' => [
                            'group' => __('marketplace.panel.group_label'),
                            'private' => __('marketplace.panel.private_label'),
                            'people' => __('common.people'),
                            'docsCount' => __('marketplace.panel.review_docs_count'),
                        ],
                        'tempUploadUrl' => route('bookings.documents.temp'),
                        'docs' => [
                            'ticket_outbound' => $docFieldState('ticket_outbound'),
                            'ticket_return' => $docFieldState('ticket_return'),
                            'passport' => $docFieldState('passport'),
                            'itinerary' => $docFieldState('itinerary'),
                            'visa' => $docFieldState('visa'),
                        ],
                        'docLabels' => [
                            'docUploading' => __('marketplace.panel.doc_uploading'),
                            'docUploaded' => __('marketplace.panel.doc_uploaded'),
                        ],
                        'messages' => [
                            'serviceRequired' => __('marketplace.panel.step_service_required'),
                            'uploadPending' => __('marketplace.panel.step_upload_pending'),
                            'docRequired' => __('marketplace.panel.step_doc_required'),
                            'docUploadFailed' => __('marketplace.panel.doc_upload_failed'),
                            'docUploadTimeout' => __('marketplace.panel.doc_upload_timeout'),
                        ],
                    ];
                @endphp
                <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div
                        class="min-w-0"
                        x-data="bookingForm(@js($bookingFormConfig))"
                        @keydown.escape.window="if (tcOpen) tcOpen = false"
                    >
                    <x-ui.booking-stepper />

                    <div class="ui-panel-body ui-stack-compact">
                    <header class="border-b border-slate-100 pb-5">
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ __('marketplace.panel.title') }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.panel.subtitle') }}</p>
                    </header>

                    <form id="booking-form-{{ $profile->id }}" x-ref="bookingForm" method="POST" action="{{ route('bookings.store') }}" enctype="multipart/form-data" class="ui-stack-compact pt-5">
                        @csrf
                        <input type="hidden" name="muthowif_profile_id" value="{{ $profile->id }}">
                        <input type="hidden" name="start_date" value="{{ $intent['start'] }}">
                        <input type="hidden" name="end_date" value="{{ $intent['end'] }}">

                        <div class="hidden" aria-hidden="true">
                            <template x-for="field in docFieldKeys" :key="'path-' + field">
                                <input type="hidden" :name="'temp_' + field + '_path'" :value="docs[field].path">
                            </template>
                            <template x-for="field in docFieldKeys" :key="'name-' + field">
                                <input type="hidden" :name="'temp_' + field + '_name'" :value="docs[field].name">
                            </template>
                        </div>

                        <div id="booking-step-1" x-show="step === 1" class="ui-stack-compact scroll-mt-20 ui-booking-step-panel">
                        @if ($tripRangeDisplay)
                            <section class="ui-booking-section-card">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100" aria-hidden="true">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                        </span>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.stay_period') }}</p>
                                            <p class="mt-0.5 text-sm font-bold tabular-nums text-slate-900">{{ $tripRangeDisplay }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ $changeDatesUrl }}" class="ui-btn-secondary shrink-0 px-4 py-2 text-xs">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                        {{ __('marketplace.panel.change_dates') }}
                                    </a>
                                </div>
                            </section>
                        @endif

                        <section class="ui-booking-section-card ui-stack-compact">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">{{ __('marketplace.panel.choose_package') }}</h2>
                                <p class="mt-0.5 text-xs text-slate-500">{{ __('marketplace.panel.choose_package_help') }}</p>
                            </div>
                            <fieldset class="min-w-0">
                                <legend class="sr-only">{{ __('marketplace.panel.choose_package') }}</legend>
                                <div class="grid grid-cols-1 gap-3 {{ ($group && $private) ? 'sm:grid-cols-2' : '' }}">
                                    @if ($group)
                                        <label class="ui-booking-package-card ui-booking-package-card--group">
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
                                            <span class="ui-booking-package-check" aria-hidden="true">
                                                <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            </span>
                                        </label>
                                    @endif
                                    @if ($private)
                                        <label class="ui-booking-package-card ui-booking-package-card--private">
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
                                            <span class="ui-booking-package-check peer-checked:border-amber-600 peer-checked:bg-amber-600" aria-hidden="true">
                                                <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            </span>
                                        </label>
                                    @endif
                                </div>
                                @error('service_type')
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p x-show="serviceTypeError" x-text="serviceTypeError" x-cloak class="mt-2 text-xs text-red-600"></p>
                            </fieldset>

                            <div class="flex flex-col gap-3 rounded-xl border border-slate-200/90 bg-slate-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <label for="pilgrim_count" class="text-sm font-semibold text-slate-800">{{ __('marketplace.panel.pilgrim_count') }}</label>
                                <div class="flex items-center gap-2">
                                    <div class="ui-booking-qty">
                                        <button type="button" @click="adjustPilgrim(-1)" class="ui-booking-qty-btn" aria-label="-">−</button>
                                        <input
                                            type="number"
                                            name="pilgrim_count"
                                            id="pilgrim_count"
                                            required
                                            inputmode="numeric"
                                            autocomplete="off"
                                            x-model.number.lazy="pilgrimCount"
                                            @change="syncPilgrimFromInput()"
                                            x-bind:min="pilgrimMin"
                                            x-bind:max="pilgrimMax"
                                            class="ui-booking-qty-input"
                                        >
                                        <button type="button" @click="adjustPilgrim(1)" class="ui-booking-qty-btn" aria-label="+">+</button>
                                    </div>
                                    <span class="text-sm text-slate-500">{{ __('common.people') }}</span>
                                </div>
                            </div>
                            @error('pilgrim_count')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            <div>
                                <label for="affiliate_code_{{ $profile->id }}" class="text-sm font-semibold text-slate-800">Kode Affiliate (opsional)</label>
                                <input
                                    id="affiliate_code_{{ $profile->id }}"
                                    type="text"
                                    name="affiliate_code"
                                    value="{{ old('affiliate_code') }}"
                                    maxlength="32"
                                    autocomplete="off"
                                    placeholder="Contoh: RIDWAN"
                                    class="mt-1 block w-full rounded-xl border-slate-300 font-mono uppercase text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                <p class="mt-1 text-xs text-slate-500">Masukkan kode affiliate jika Anda datang dari referral.</p>
                                @error('affiliate_code')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </section>

                        <section class="ui-stack-compact lg:hidden">
                            <h2 class="text-sm font-bold text-slate-900">{{ __('marketplace.panel.review_heading') }}</h2>
                            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.stay_period') }}</p>
                                    <p class="mt-1 text-xs font-bold leading-snug tabular-nums text-slate-900">{{ $tripRangeDisplay ?? '—' }}</p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.choose_package') }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-900" x-text="serviceLabelDisplay"></p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.pilgrim_count') }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-900"><span x-text="pilgrimCount"></span> {{ __('common.people') }}</p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.review_docs') }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-900" x-text="docsCountDisplay"></p>
                                </div>
                            </div>
                        </section>

                        <section class="ui-booking-section-card ui-stack-compact">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">{{ __('marketplace.panel.addons_toggle') }}</h2>
                            </div>
                            <div class="space-y-3">
                                    @if ($group)
                                        <fieldset
                                            class="space-y-3 border-0 p-0 m-0 min-w-0"
                                            @if ($private) x-show="serviceIsGroup" x-cloak :disabled="!serviceIsGroup" @endif
                                        >
                                            @php
                                                $groupHotelAvailable = ($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0;
                                                $groupTransportAvailable = ($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0;
                                            @endphp

                                            @include('layanan.partials.booking-addon-choices', [
                                                'hotelAvailable' => $groupHotelAvailable,
                                                'transportAvailable' => $groupTransportAvailable,
                                                'hotelPricePerDay' => (int) ($group->same_hotel_price_per_day ?? 0),
                                                'transportPriceFlat' => (int) ($group->transport_price_flat ?? 0),
                                                'oldWithSameHotel' => $oldWithSameHotel,
                                                'oldWithTransport' => $oldWithTransport,
                                                'accent' => 'brand',
                                                'idSuffix' => 'group',
                                            ])
                                        </fieldset>
                                    @endif

                                    @if ($private)
                                        <fieldset
                                            class="space-y-3 border-0 p-0 m-0 min-w-0"
                                            @if ($group) x-show="!serviceIsGroup" x-cloak :disabled="serviceIsGroup" @endif
                                        >
                                            @php
                                                $privateHotelAvailable = ($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0;
                                                $privateTransportAvailable = ($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0;
                                            @endphp

                                            @if ($private->addOns->isNotEmpty())
                                                @foreach ($private->addOns as $addon)
                                                    <label class="flex cursor-pointer items-start gap-3">
                                                        <input type="checkbox" name="add_on_ids[]" value="{{ $addon->id }}"
                                                            class="mt-1 size-4 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
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

                                            @include('layanan.partials.booking-addon-choices', [
                                                'hotelAvailable' => $privateHotelAvailable,
                                                'transportAvailable' => $privateTransportAvailable,
                                                'hotelPricePerDay' => (int) ($private->same_hotel_price_per_day ?? 0),
                                                'transportPriceFlat' => (int) ($private->transport_price_flat ?? 0),
                                                'oldWithSameHotel' => $oldWithSameHotel,
                                                'oldWithTransport' => $oldWithTransport,
                                                'accent' => 'amber',
                                                'idSuffix' => 'private',
                                            ])
                                        </fieldset>
                                    @endif
                            </div>
                        </section>

                        <div class="border-t border-slate-100 pt-4">
                            <button type="button" @click="nextStep()" class="ui-btn-primary w-full py-3.5 text-base font-bold">
                                {{ __('marketplace.panel.continue_to_documents') }}
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.358-4.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        </div>

                        <template x-if="step === 2">
                        <fieldset id="booking-step-2" class="scroll-mt-20 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 sm:p-5 ui-booking-step-panel">
                            <legend class="text-base font-bold text-slate-900">{{ __('marketplace.panel.docs_heading') }}</legend>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('marketplace.panel.docs_intro_short') }}</p>

                            <div class="mt-4 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <label for="ticket_outbound_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_ticket_outbound') }} <span class="text-red-600">*</span></label>
                                    <div x-show="docs.ticket_outbound.path" x-cloak class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                        <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <span class="truncate" x-text="docUploadedLabel(docs.ticket_outbound.name)"></span>
                                    </div>

                                    <input id="ticket_outbound_{{ $profile->id }}" type="file" name="ticket_outbound"
                                           x-bind:required="!docs.ticket_outbound.path"
                                           @change="uploadBookingDoc('ticket_outbound', $event)"
                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    <p x-show="docs.ticket_outbound.uploading" x-cloak class="mt-1 text-xs text-brand-700" x-text="docLabels.docUploading"></p>
                                    <p x-show="docs.ticket_outbound.error && !docs.ticket_outbound.uploading" x-text="docs.ticket_outbound.error" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    @error('ticket_outbound')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="ticket_return_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_ticket_return') }} <span class="text-red-600">*</span></label>
                                    <div x-show="docs.ticket_return.path" x-cloak class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                        <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <span class="truncate" x-text="docUploadedLabel(docs.ticket_return.name)"></span>
                                    </div>

                                    <input id="ticket_return_{{ $profile->id }}" type="file" name="ticket_return"
                                           x-bind:required="!docs.ticket_return.path"
                                           @change="uploadBookingDoc('ticket_return', $event)"
                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    <p x-show="docs.ticket_return.uploading" x-cloak class="mt-1 text-xs text-brand-700" x-text="docLabels.docUploading"></p>
                                    <p x-show="docs.ticket_return.error && !docs.ticket_return.uploading" x-text="docs.ticket_return.error" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    @error('ticket_return')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="passport_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_passport') }} <span class="text-red-600">*</span></label>
                                    <div x-show="docs.passport.path" x-cloak class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                        <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <span class="truncate" x-text="docUploadedLabel(docs.passport.name)"></span>
                                    </div>

                                    <input id="passport_{{ $profile->id }}" type="file" name="passport"
                                           x-bind:required="!docs.passport.path"
                                           @change="uploadBookingDoc('passport', $event)"
                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100" />
                                    <p x-show="docs.passport.uploading" x-cloak class="mt-1 text-xs text-brand-700" x-text="docLabels.docUploading"></p>
                                    <p x-show="docs.passport.error && !docs.passport.uploading" x-text="docs.passport.error" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    @error('passport')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="itinerary_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">
                                        {{ __('marketplace.panel.doc_itinerary') }}
                                        <span x-show="serviceIsGroup" class="text-red-600">*</span>
                                        <span x-show="!serviceIsGroup" x-cloak class="font-normal text-slate-400">({{ __('marketplace.panel.doc_optional') }})</span>
                                    </label>
                                    <div x-show="docs.itinerary.path" x-cloak class="mt-2 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                        <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <span class="truncate" x-text="docUploadedLabel(docs.itinerary.name)"></span>
                                    </div>

                                    <input id="itinerary_{{ $profile->id }}" type="file" name="itinerary" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           x-bind:required="serviceIsGroup && !docs.itinerary.path"
                                           @change="uploadBookingDoc('itinerary', $event)"
                                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                    <p x-show="docs.itinerary.uploading" x-cloak class="mt-1 text-xs text-brand-700" x-text="docLabels.docUploading"></p>
                                    <p x-show="docs.itinerary.error && !docs.itinerary.uploading" x-text="docs.itinerary.error" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    @error('itinerary')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="visa_{{ $profile->id }}" class="block text-sm font-semibold text-slate-800">{{ __('marketplace.panel.doc_visa') }} <span class="text-slate-400">({{ __('marketplace.panel.doc_optional') }})</span></label>
                                    
                                    <div x-show="docs.visa.path" x-cloak class="mt-2 flex max-w-lg items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                                        <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <span class="truncate" x-text="docUploadedLabel(docs.visa.name)"></span>
                                    </div>

                                    <input id="visa_{{ $profile->id }}" type="file" name="visa" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                           @change="uploadBookingDoc('visa', $event)"
                                           class="mt-2 block w-full max-w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 sm:max-w-lg" />
                                    <p x-show="docs.visa.uploading" x-cloak class="mt-1 text-xs text-brand-700" x-text="docLabels.docUploading"></p>
                                    <p x-show="docs.visa.error && !docs.visa.uploading" x-text="docs.visa.error" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    @error('visa')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                </div>

                        <p x-show="uploadPendingError" x-text="uploadPendingError" x-cloak class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900"></p>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-between">
                            <button type="button" @click="prevStep()" class="ui-btn-secondary w-full sm:w-auto">
                                {{ __('marketplace.panel.step_back') }}
                            </button>
                            <button
                                type="button"
                                @click="nextStep()"
                                :disabled="docUploading"
                                class="ui-btn-primary w-full py-3.5 text-base font-bold sm:w-auto sm:min-w-[12rem]"
                            >
                                {{ __('marketplace.panel.continue_to_confirm') }}
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.358-4.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        </fieldset>
                        </template>

                        <template x-if="step === 3">
                        <div id="booking-step-3" class="scroll-mt-20 ui-stack-compact ui-booking-step-panel">
                            <div>
                                <p class="text-base font-bold text-slate-900">{{ __('marketplace.panel.step_confirm_title') }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.panel.step_confirm_sub') }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.stay_period') }}</p>
                                    <p class="mt-1 text-xs font-bold tabular-nums text-slate-900">{{ $tripRangeDisplay ?? $rangeLabel }}</p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.choose_package') }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-900" x-text="serviceLabelDisplay"></p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.pilgrim_count') }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-900"><span x-text="pilgrimCount"></span> {{ __('common.people') }}</p>
                                </div>
                                <div class="ui-booking-review-chip">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.panel.review_docs') }}</p>
                                    <p class="mt-1 text-xs font-bold text-emerald-700" x-text="docsCountDisplay"></p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" @click="prevStep()" class="ui-btn-secondary order-2 w-full sm:order-1 sm:w-auto">
                                    {{ __('marketplace.panel.step_back') }}
                                </button>
                                <div class="order-1 flex w-full flex-col gap-3 sm:order-2 sm:w-auto sm:items-end">
                                    <button type="button"
                                            @click="openBookingTc()"
                                            class="inline-flex w-full min-h-[3rem] items-center justify-center rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-brand-900/18 transition hover:from-brand-500 hover:to-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:min-w-[12rem]">
                                        {{ __('marketplace.panel.submit') }}
                                    </button>
                                    <p class="max-w-sm text-center text-[11px] leading-snug text-slate-500 sm:text-right">
                                        {!! __('marketplace.panel.consent_html') !!}
                                    </p>
                                </div>
                            </div>
                        </div>
                        </template>
                    </form>
                    </div>

                    <div
                        x-show="tcOpen"
                        x-cloak
                        class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="'tc-title-{{ $profile->id }}'"
                    >
                        <div class="absolute inset-0 bg-slate-900/70" @click="tcOpen = false" aria-hidden="true"></div>
                        <div
                            class="relative z-10 flex max-h-[min(90vh,40rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-3xl"
                            @click.stop
                        >
                            <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                                <h3 id="tc-title-{{ $profile->id }}" class="text-base font-bold text-slate-900">{{ __('marketplace.panel.tc_title') }}</h3>
                            </div>
                            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 text-sm leading-relaxed text-slate-600 sm:px-6">
                                {!! __('marketplace.panel.tc_body_html') !!}
                                <p class="mt-4 border-t border-slate-100 pt-4">
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-brand-700 underline decoration-brand-700/40 underline-offset-2 hover:text-brand-800">{{ __('marketplace.panel.tc_full_text') }}</a>
                                </p>
                                <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3">
                                    <input type="checkbox" x-model="tcAgree" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                                    <span class="text-sm font-medium text-slate-800">{{ __('marketplace.panel.tc_checkbox') }}</span>
                                </label>
                            </div>
                            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:gap-3 sm:px-6">
                                <button type="button" @click="tcOpen = false" class="ui-btn-secondary w-full sm:w-auto">
                                    {{ __('marketplace.panel.tc_cancel') }}
                                </button>
                                <button
                                    type="button"
                                    @click="submitAfterTc()"
                                    :disabled="!tcAgree"
                                    class="ui-btn-primary w-full sm:w-auto"
                                    :class="!tcAgree && 'cursor-not-allowed opacity-50'"
                                >
                                    {{ __('marketplace.panel.tc_submit') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="hidden border-l border-slate-100 bg-slate-50/40 px-5 py-6 lg:block" x-data="bookingSummaryAside()">
                        @include('layanan.partials.booking-trip-aside', [
                            'profile' => $profile,
                            'tripRangeLabel' => $tripRangeDisplay ?? $tripRangeLabel,
                            'canSubmit' => $canSubmit,
                            'group' => $group,
                            'private' => $private,
                        ])
                    </div>
                </div>
            @endif
        @endif
</section>
