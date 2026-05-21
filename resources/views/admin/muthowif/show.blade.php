<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ALERT --}}
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- HEADER --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                    <div class="flex items-center gap-5">
                        <img
                            src="{{ route('admin.muthowif.photo', $profile) }}"
                            alt="{{ __('admin.muthowif.photo_alt') }}"
                            class="w-24 h-24 rounded-3xl object-cover border border-slate-200 shadow-sm"
                        >

                        <div>
                            <p class="text-sm text-slate-500">
                                {{ __('admin.muthowif.name') }}
                            </p>

                            <h1 class="text-2xl font-bold text-slate-900">
                                {{ $profile->user->name }}
                            </h1>

                            <p class="mt-1 text-slate-600">
                                {{ $profile->user->email }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-3">

                                <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold
                                    @switch($profile->verification_status)
                                        @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                            bg-amber-100 text-amber-800
                                            @break
                                        @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                            bg-emerald-100 text-emerald-700
                                            @break
                                        @default
                                            bg-rose-100 text-rose-700
                                    @endswitch
                                ">
                                    {{ $profile->verification_status->label() }}
                                </span>

                                <span class="text-sm text-slate-500">
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </span>

                            </div>
                        </div>
                    </div>

                    @if ($profile->isPending())
                        <div class="flex flex-wrap gap-3">

                            <form method="POST" action="{{ route('admin.muthowif.approve', $profile) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 shadow-sm"
                                >
                                    {{ __('admin.muthowif.approve_registration') }}
                                </button>
                            </form>

                        </div>
                    @endif
                </div>
            </div>

            {{-- MAIN GRID --}}
            <div class="grid grid-cols-12 gap-6">

                {{-- LEFT --}}
                <div class="col-span-12 lg:col-span-8 space-y-6">

                    {{-- BIODATA --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="mb-6">
                            <h2 class="text-lg font-bold text-slate-900">
                                Biodata
                            </h2>

                            <p class="text-sm text-slate-500">
                                Informasi lengkap muthowif
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

                            <div>
                                <p class="text-slate-400 mb-1">
                                    WhatsApp
                                </p>

                                <h3 class="font-semibold text-slate-900">
                                    {{ $profile->phone }}
                                </h3>
                            </div>

                            <div>
                                <p class="text-slate-400 mb-1">
                                    Tanggal Lahir
                                </p>

                                <h3 class="font-semibold text-slate-900">
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </h3>
                            </div>

                            <div>
                                <p class="text-slate-400 mb-1">
                                    NIK
                                </p>

                                <h3 class="font-semibold text-slate-900">
                                    {{ $profile->nik }}
                                </h3>
                            </div>

                            <div>
                                <p class="text-slate-400 mb-1">
                                    Passport
                                </p>

                                <h3 class="font-semibold text-slate-900">
                                    {{ $profile->passport_number ?? '—' }}
                                </h3>
                            </div>

                            <div class="md:col-span-2">
                                <p class="text-slate-400 mb-1">
                                    Alamat
                                </p>

                                <h3 class="font-semibold text-slate-900 whitespace-pre-line leading-relaxed">
                                    {{ $profile->address }}
                                </h3>
                            </div>

                        </div>
                    </div>

                    {{-- BAHASA --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-5">
                            Bahasa
                        </h2>

                        <div class="flex flex-wrap gap-3">
                            @foreach ($profile->languagesForDisplay() as $item)
                                <span class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- PENDIDIKAN --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-5">
                            Pendidikan
                        </h2>

                        <div class="space-y-3">
                            @foreach ($profile->educationsForDisplay() as $item)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                                    <p class="font-medium text-slate-800">
                                        {{ $item }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- PENGALAMAN --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-5">
                            Pengalaman Kerja
                        </h2>

                        <div class="space-y-3">
                            @foreach ($profile->workExperiencesForDisplay() as $item)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                                    <p class="font-medium text-slate-800">
                                        {{ $item }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- REFERENSI --}}
                    @if (filled($profile->reference_text))
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-bold text-slate-900 mb-4">
                                Referensi
                            </h2>

                            <p class="text-slate-700 leading-relaxed whitespace-pre-line">
                                {{ $profile->reference_text }}
                            </p>
                        </div>
                    @endif

                </div>

                {{-- RIGHT --}}
                <div class="col-span-12 lg:col-span-4 space-y-6">

                    {{-- FOTO --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sticky top-24">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">
                            Foto Profil
                        </h2>

                        <div class="rounded-3xl overflow-hidden bg-slate-100 p-3">
                            <img
                                src="{{ route('admin.muthowif.photo', $profile) }}"
                                alt="{{ __('admin.muthowif.photo_alt') }}"
                                class="w-full rounded-2xl object-cover"
                            />
                        </div>
                    </div>

                    {{-- KTP --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">
                            KTP
                        </h2>

                        <div class="rounded-3xl overflow-hidden bg-slate-100 p-3">
                            <img
                                src="{{ route('admin.muthowif.ktp', $profile) }}"
                                alt="{{ __('admin.muthowif.ktp_alt') }}"
                                class="w-full rounded-2xl object-cover"
                            />
                        </div>
                    </div>

                    {{-- DOKUMEN --}}
                    @if ($profile->supportingDocuments->isNotEmpty())
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-bold text-slate-900 mb-4">
                                Dokumen Pendukung
                            </h2>

                            <div class="space-y-3">
                                @foreach ($profile->supportingDocuments as $doc)
                                    <a
                                        href="{{ route('admin.muthowif.document', [$profile, $doc]) }}"
                                        target="_blank"
                                        class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 transition hover:bg-slate-100"
                                    >
                                        <span class="text-sm font-medium text-slate-700">
                                            {{ $doc->original_name ?? basename($doc->path) }}
                                        </span>

                                        <span class="text-sm font-semibold text-brand-700">
                                            Buka
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- REJECT --}}
                    @if ($profile->isPending())
                        <div class="rounded-3xl border border-rose-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-bold text-rose-700 mb-4">
                                Tolak Pendaftaran
                            </h2>

                            <form
                                method="POST"
                                action="{{ route('admin.muthowif.reject', $profile) }}"
                                class="space-y-4"
                            >
                                @csrf

                                <textarea
                                    id="rejection_reason"
                                    name="rejection_reason"
                                    rows="4"
                                    class="block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                    placeholder="{{ __('admin.muthowif.reject_placeholder') }}"
                                >{{ old('rejection_reason') }}</textarea>

                                <x-input-error :messages="$errors->get('rejection_reason')" />

                                <button
                                    type="submit"
                                    class="w-full rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700"
                                >
                                    {{ __('admin.muthowif.reject_submit') }}
                                </button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>