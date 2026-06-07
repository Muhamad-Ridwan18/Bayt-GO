@props([
    'name' => 'phone',
    'value' => null,
    'country' => null,
    'inputId' => 'phone_local',
    'selectId' => 'phone_country_trigger',
    'label' => null,
    'hint' => null,
    'required' => false,
    'errorKey' => 'phone',
])

@php
    $dialInit = \App\Support\IntlPhone::defaultDialCode();
    $nationalInit = '';
    if ($value !== null && $value !== '') {
        if (($parts = \App\Support\IntlPhone::dialAndNational($value))) {
            $dialInit = $parts['dial'];
            $nationalInit = $parts['national'];
        }
    }

    /** Satu entri per region (ISO 3166-1 alpha-2); nama dari ICU mengikuti locale aplikasi. */
    $countries = \App\Support\IntlPhone::countriesForPhonePicker();

    if ($nationalInit !== '' || ($value !== null && $value !== '')) {
        $inList = false;
        foreach ($countries as $row) {
            if ($row['d'] === $dialInit) {
                $inList = true;
                break;
            }
        }
        if (! $inList && $dialInit !== '') {
            array_unshift($countries, ['d' => $dialInit, 'iso' => '', 'flag' => '🌐', 'name' => 'Other']);
        }
    }

    $countryIsoInit = '';
    if (is_string($country) && strlen(trim($country)) === 2) {
        $countryIsoInit = strtoupper(trim($country));
    }
    if ($countryIsoInit === '' && $value !== null && $value !== '') {
        $countryIsoInit = \App\Support\IntlPhone::regionForNumber($value) ?? '';
    }
    if ($countryIsoInit === '') {
        foreach ($countries as $row) {
            if (($row['d'] ?? '') === $dialInit && ! empty($row['iso'])) {
                $countryIsoInit = $row['iso'];
                break;
            }
        }
    }
@endphp

<div
    class="relative space-y-2"
    x-data='{
        countryOpen: false,
        countryQuery: "",
        countries: @json($countries),
        phoneDial: @json($dialInit),
        phoneNational: @json($nationalInit),
        countryIso: @json($countryIsoInit),
        filteredCountries() {
            const q = this.countryQuery.trim().toLowerCase().replace(/^\+/, "");
            if (!q) return this.countries;
            return this.countries.filter((c) => {
                const name = String(c.name || "").toLowerCase();
                const dial = String(c.d || "");
                const iso = String(c.iso || "").toLowerCase();
                return name.includes(q) || dial.includes(q) || iso.includes(q);
            });
        },
        pick(c) {
            this.phoneDial = c.d;
            this.countryIso = c.iso ? c.iso : "";
            this.countryOpen = false;
            this.countryQuery = "";
        },
        toggleCountryPicker() {
            this.countryOpen = !this.countryOpen;
            if (this.countryOpen) {
                this.countryQuery = "";
                this.$nextTick(() => this.$refs.countrySearch?.focus());
            }
        },
        selected() {
            if (this.countryIso) {
                const byIso = this.countries.find((c) => c.iso === this.countryIso);
                if (byIso) {
                    return byIso;
                }
            }
            const x = this.countries.find((c) => c.d === this.phoneDial && c.iso);
            if (x) {
                return x;
            }
            return { d: this.phoneDial, flag: "🌐", name: "Other", iso: "" };
        },
        fullPhone() {
            const d = String(this.phoneDial).replace(/\D/g, "");
            let l = String(this.phoneNational).replace(/\D/g, "");
            if (d === "62" && l.startsWith("0")) l = l.slice(1);
            return (d && l) ? "+" + d + l : "";
        },
        placeholderNat() {
            const d = String(this.phoneDial).replace(/\D/g, "");
            if (d === "1") return "(201) 555-0123";
            if (d === "62") return "812 3456 7890";
            if (d === "44") return "7700 900123";
            return "8123456789";
        }
    }'
    @keydown.escape.window="countryOpen = false"
>
    @if ($label)
        <x-input-label for="{{ $inputId }}" :value="$label" :required="$required" />
    @endif

    <div
        class="flex rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 overflow-visible"
        @click.outside="countryOpen = false"
    >
        <div class="relative shrink-0">
            <span id="{{ $selectId }}-sr" class="sr-only">{{ __('auth_custom.phone_country_label') }}</span>
            <button
                type="button"
                id="{{ $selectId }}"
                class="flex items-center gap-2 h-full min-h-[2.75rem] ps-3 pe-3 sm:pe-2 border-e border-slate-200 bg-slate-50/90 hover:bg-slate-100 rounded-s-xl text-left transition"
                @click="toggleCountryPicker()"
                :aria-expanded="countryOpen"
                aria-haspopup="listbox"
                aria-controls="country-listbox-{{ $inputId }}"
            >
                <span class="text-xl leading-none" x-text="selected().flag" aria-hidden="true"></span>
                <span class="hidden sm:inline text-sm font-medium text-slate-700 tabular-nums" x-text="'+' + phoneDial"></span>
                <svg class="w-4 h-4 text-slate-500 shrink-0 sm:ms-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <div
                x-show="countryOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-0 top-full z-[100] mt-1 flex w-[min(100vw-2rem,20rem)] max-h-72 flex-col rounded-xl border border-slate-200 bg-white shadow-lg"
                x-cloak
                role="listbox"
                id="country-listbox-{{ $inputId }}"
            >
                <div class="sticky top-0 z-10 border-b border-slate-100 bg-white p-2">
                    <input
                        type="search"
                        x-ref="countrySearch"
                        x-model="countryQuery"
                        autocomplete="off"
                        autocorrect="off"
                        spellcheck="false"
                        placeholder="{{ __('auth_custom.phone_country_search_placeholder') }}"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                        @keydown.escape.stop="countryOpen = false"
                        @click.stop
                    />
                </div>
                <div class="max-h-52 overflow-y-auto py-1">
                <template x-for="c in filteredCountries()" :key="c.iso ? c.iso : 'dial-' + c.d">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm hover:bg-slate-100 transition"
                        :class="phoneDial === c.d ? 'bg-brand-50/80' : ''"
                        role="option"
                        :aria-selected="phoneDial === c.d"
                        @click="pick(c)"
                    >
                        <span class="text-lg leading-none w-8 text-center shrink-0" x-text="c.flag"></span>
                        <span class="flex-1 min-w-0 truncate font-medium text-slate-900" x-text="c.name"></span>
                        <span class="shrink-0 text-slate-500 tabular-nums" x-text="'+' + c.d"></span>
                    </button>
                </template>
                <p
                    x-show="filteredCountries().length === 0"
                    class="px-3 py-4 text-center text-sm text-slate-500"
                >{{ __('auth_custom.phone_country_no_results') }}</p>
                </div>
            </div>
        </div>

        <input
            id="{{ $inputId }}"
            type="tel"
            inputmode="tel"
            autocomplete="tel-national"
            x-model="phoneNational"
            x-bind:placeholder="placeholderNat()"
            @if ($required) required @endif
            class="min-w-0 flex-1 border-0 bg-transparent py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 rounded-e-xl"
        />
    </div>

    <input type="hidden" name="{{ $name }}" x-bind:value="fullPhone()" />
    <input type="hidden" name="country" x-bind:value="countryIso" />

    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-1" />
    <x-input-error :messages="$errors->get('country')" class="mt-1" />
</div>
