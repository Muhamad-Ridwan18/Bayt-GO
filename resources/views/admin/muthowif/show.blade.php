<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8">
        <x-page-container class="space-y-5">

            {{-- STATUS BANNER --}}
            @if ($profile->isPending())
                <x-ui.alert type="warning" class="flex items-center gap-3">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pendaftaran muthowif ini sedang menunggu persetujuan admin
                </x-ui.alert>
            @endif

            @if ($profile->isApproved() && ! $profile->isActiveAccount())
                <x-ui.alert type="error" class="flex items-center gap-3">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ __('admin.muthowif.account_status_inactive_banner', ['status' => ($profile->account_status ?? \App\Enums\MuthowifAccountStatus::Active)->label()]) }}
                </x-ui.alert>
            @endif

            {{-- HEADER CARD --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">

                    {{-- PROFILE INFO --}}
                    <div class="flex items-center gap-4">
                        <div class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <img src="{{ route('admin.muthowif.photo', $profile) }}"
                                alt="{{ __('admin.muthowif.photo_alt') }}"
                                class="h-full w-full object-cover">
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h1 class="text-lg font-semibold text-slate-900">{{ $profile->user->name }}</h1>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @switch($profile->verification_status)
                                        @case(\App\Enums\MuthowifVerificationStatus::Pending) bg-amber-100 text-amber-700 @break
                                        @case(\App\Enums\MuthowifVerificationStatus::Approved) bg-emerald-100 text-emerald-700 @break
                                        @default bg-red-100 text-red-700
                                    @endswitch">
                                    @switch($profile->verification_status)
                                        @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @break
                                        @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @break
                                        @default
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endswitch
                                    {{ $profile->verification_status->label() }}
                                </span>
                                @if ($profile->isApproved())
                                    @php $accountStatus = $profile->account_status ?? \App\Enums\MuthowifAccountStatus::Active; @endphp
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @switch($accountStatus)
                                            @case(\App\Enums\MuthowifAccountStatus::Active) bg-sky-100 text-sky-700 @break
                                            @case(\App\Enums\MuthowifAccountStatus::Suspended) bg-orange-100 text-orange-700 @break
                                            @default bg-red-100 text-red-700
                                        @endswitch">
                                        {{ $accountStatus->label() }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500">{{ $profile->user->email }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </span>
                                @if ($profile->phone)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $profile->phone }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ACTION --}}
                    @if ($profile->isPending())
                        <form method="POST" action="{{ route('admin.muthowif.approve', $profile) }}" class="flex-shrink-0">
                            @csrf
                            <x-submit-button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 active:scale-95">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('admin.muthowif.approve_registration') }}
                            </x-submit-button>
                        </form>
                    @endif

                </div>
            </div>

            {{-- MAIN GRID --}}
            <div class="grid grid-cols-12 gap-5">

                {{-- LEFT --}}
                <div class="col-span-12 xl:col-span-8 space-y-5">

                    {{-- BIODATA --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
                                <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Biodata</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div>
                                <p class="text-xs text-slate-400 mb-1">NIK</p>
                                <p class="text-sm font-medium text-slate-900">{{ $profile->nik }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-1">Nomor Passport</p>
                                <p class="text-sm font-medium text-slate-900">{{ $profile->passport_number ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-1">Tanggal Lahir</p>
                                <p class="text-sm font-medium text-slate-900">{{ $profile->birth_date?->translatedFormat('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-1">WhatsApp</p>
                                <p class="text-sm font-medium text-slate-900">{{ $profile->phone }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-400 mb-1">Alamat sesuai KTP</p>
                                <p class="text-sm font-medium leading-relaxed text-slate-900">{{ $profile->address ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-1">Lokasi kerja saat ini</p>
                                <p class="text-sm font-medium text-slate-900">{{ $profile->workLocationLabel() ?: '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- PENGALAMAN --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
                                <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Pengalaman Kerja</h2>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($profile->workExperiencesForDisplay() as $item)
                                <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500"></span>
                                    <p class="text-sm text-slate-700">{{ $item }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- BAHASA & PENDIDIKAN --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center gap-2.5 mb-5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
                                    <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                                </div>
                                <h2 class="text-base font-semibold text-slate-900">Bahasa</h2>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($profile->languagesForDisplay() as $item)
                                    <span class="rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700">
                                        {{ $item }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center gap-2.5 mb-5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
                                    <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
                                </div>
                                <h2 class="text-base font-semibold text-slate-900">Pendidikan</h2>
                            </div>
                            <div class="space-y-2">
                                @foreach ($profile->educationsForDisplay() as $item)
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-2.5">
                                        <p class="text-sm text-slate-700">{{ $item }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>

                </div>

                {{-- RIGHT --}}
                <div class="col-span-12 xl:col-span-4 space-y-5">

                    {{-- FOTO PROFIL --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h2 class="text-sm font-semibold text-slate-700">Foto Profil</h2>
                        </div>
                        <div class="overflow-hidden rounded-xl bg-slate-100">
                            <img src="{{ route('admin.muthowif.photo', $profile) }}"
                                alt="{{ __('admin.muthowif.photo_alt') }}"
                                class="w-full max-h-72 object-contain">
                        </div>
                    </div>

                    {{-- KTP --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                            <h2 class="text-sm font-semibold text-slate-700">KTP</h2>
                        </div>
                        <div class="overflow-hidden rounded-xl bg-slate-100">
                            <img src="{{ route('admin.muthowif.ktp', $profile) }}"
                                alt="{{ __('admin.muthowif.ktp_alt') }}"
                                class="w-full object-contain">
                        </div>
                    </div>

                    {{-- DOKUMEN PENDUKUNG --}}
                    @if ($profile->supportingDocuments->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <h2 class="text-sm font-semibold text-slate-700">Dokumen Pendukung</h2>
                            </div>
                            <div class="space-y-2">
                                @foreach ($profile->supportingDocuments as $doc)
                                    <a href="{{ route('admin.muthowif.document', [$profile, $doc]) }}" target="_blank"
                                        class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="truncate text-xs font-medium text-slate-700">
                                                {{ $doc->original_name ?? basename($doc->path) }}
                                            </span>
                                        </div>
                                        <span class="ml-3 flex-shrink-0 text-xs font-semibold text-violet-600">Buka ↗</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- STATUS AKUN (setelah disetujui) --}}
            @if ($profile->isApproved())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                            <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('admin.muthowif.account_status_heading') }}</h2>
                    </div>
                    <p class="mb-5 ml-10 text-sm text-slate-500">{{ __('admin.muthowif.account_status_hint') }}</p>

                    <form method="POST" action="{{ route('admin.muthowif.account_status', $profile) }}" class="ml-10 flex flex-col gap-4 sm:flex-row sm:items-end">
                        @csrf
                        <div class="min-w-[12rem] flex-1 sm:max-w-xs">
                            <label for="account_status" class="block text-sm font-medium text-slate-700">{{ __('admin.muthowif.account_status_field') }}</label>
                            <select
                                id="account_status"
                                name="account_status"
                                class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-violet-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-100">
                                @foreach (\App\Enums\MuthowifAccountStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('account_status', ($profile->account_status ?? \App\Enums\MuthowifAccountStatus::Active)->value) === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('account_status')" />
                        </div>
                        <x-submit-button class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-violet-700 active:scale-95">
                            {{ __('admin.muthowif.account_status_save') }}
                        </x-submit-button>
                    </form>
                </div>
            @endif

            {{-- REJECT --}}
            @if ($profile->isPending())
                <div class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-red-700">Tolak Pendaftaran</h2>
                    </div>
                    <p class="mb-5 ml-10 text-sm text-slate-500">{{ __('admin.muthowif.reject_placeholder_hint', ['default' => 'Berikan alasan penolakan agar muthowif dapat memperbaiki pendaftarannya']) }}</p>

                    <form method="POST" action="{{ route('admin.muthowif.reject', $profile) }}" class="ui-stack-tight">
                        @csrf

                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            rows="4"
                            placeholder="{{ __('admin.muthowif.reject_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-red-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100">{{ old('rejection_reason') }}</textarea>

                        <x-input-error :messages="$errors->get('rejection_reason')" />

                        <x-submit-button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 active:scale-95">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            {{ __('admin.muthowif.reject_submit') }}
                        </x-submit-button>

                    </form>
                </div>
            @endif

            {{-- NOTIFY REJECTION VIA WHATSAPP --}}
            @if ($profile->isRejected())
                <div class="rounded-2xl border border-amber-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50">
                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('admin.muthowif.notify_rejection_heading') }}</h2>
                    </div>
                    <p class="mb-4 ml-10 text-sm text-slate-500">{{ __('admin.muthowif.notify_rejection_hint') }}</p>

                    <form method="POST" action="{{ route('admin.muthowif.notify_rejection', $profile) }}" class="ml-10 space-y-4">
                        @csrf

                        <div>
                            <label for="rejection_note" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('admin.muthowif.notify_rejection_note_label') }}</label>
                            <textarea
                                id="rejection_note"
                                name="rejection_note"
                                rows="4"
                                placeholder="{{ __('admin.muthowif.notify_rejection_note_placeholder') }}"
                                class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('rejection_note', $profile->rejection_reason) }}</textarea>
                            <x-input-error :messages="$errors->get('rejection_note')" />
                            <p class="mt-1.5 text-xs text-slate-400">{{ __('admin.muthowif.notify_rejection_note_help') }}</p>
                        </div>

                        <x-submit-button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 active:scale-95">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            {{ __('admin.muthowif.notify_rejection_submit') }}
                        </x-submit-button>
                    </form>
                </div>
            @endif

        </x-page-container>
    </div>
</x-app-layout>