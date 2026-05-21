<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto max-w-[1500px] px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ALERT --}}
            @if (session('status'))
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- HEADER --}}
            <div class="rounded-[32px] border border-slate-200 bg-white p-7 shadow-sm">

                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">

                    {{-- PROFILE --}}
                    <div class="flex items-center gap-5">

                        <div class="h-28 w-28 overflow-hidden rounded-[28px] border border-slate-200 bg-slate-100 shadow-sm">
                            <img
                                src="{{ route('admin.muthowif.photo', $profile) }}"
                                alt="{{ __('admin.muthowif.photo_alt') }}"
                                class="h-full w-full object-cover"
                            >
                        </div>

                        <div>

                            <p class="text-sm text-slate-500">
                                {{ __('admin.muthowif.name') }}
                            </p>

                            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                                {{ $profile->user->name }}
                            </h1>

                            <p class="mt-1 text-slate-600">
                                {{ $profile->user->email }}
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-3">

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

                                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </span>

                            </div>
                        </div>
                    </div>

                    {{-- ACTION --}}
                    @if ($profile->isPending())
                        <div class="flex flex-wrap gap-3">

                            {{-- APPROVE --}}
                            <form
                                method="POST"
                                action="{{ route('admin.muthowif.approve', $profile) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:bg-emerald-700 hover:shadow-xl"
                                >
                                    {{ __('admin.muthowif.approve_registration') }}
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            </div>

            {{-- ROW 1 --}}
            <div class="grid grid-cols-12 gap-6">

                {{-- BIODATA --}}
                <div class="col-span-12 xl:col-span-8">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-slate-900">
                                Biodata
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap muthowif
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">

                            <div>
                                <p class="text-sm text-slate-400">
                                    WhatsApp
                                </p>

                                <p class="mt-2 text-base font-semibold text-slate-900">
                                    {{ $profile->phone }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-400">
                                    Tanggal Lahir
                                </p>

                                <p class="mt-2 text-base font-semibold text-slate-900">
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-400">
                                    Passport
                                </p>

                                <p class="mt-2 text-base font-semibold text-slate-900">
                                    {{ $profile->passport_number ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-400">
                                    NIK
                                </p>

                                <p class="mt-2 text-base font-semibold text-slate-900 break-all">
                                    {{ $profile->nik }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <p class="text-sm text-slate-400">
                                    Alamat
                                </p>

                                <p class="mt-2 text-base font-semibold leading-relaxed text-slate-900 whitespace-pre-line">
                                    {{ $profile->address }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- FOTO --}}
                <div class="col-span-12 xl:col-span-4">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-slate-900">
                                Foto Profil
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Foto resmi muthowif
                            </p>
                        </div>

                        <div class="rounded-[28px] bg-slate-100 p-4">
                            <img
                                src="{{ route('admin.muthowif.photo', $profile) }}"
                                alt="{{ __('admin.muthowif.photo_alt') }}"
                                class="h-[500px] w-full rounded-2xl object-contain"
                            >
                        </div>

                    </div>
                </div>

            </div>

            {{-- ROW 2 --}}
            <div class="grid grid-cols-12 gap-6">

                {{-- PENGALAMAN --}}
                <div class="col-span-12 xl:col-span-8">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900">
                                Pengalaman Kerja
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Riwayat pengalaman dan pekerjaan
                            </p>
                        </div>

                        <div class="space-y-4">

                            @foreach ($profile->workExperiencesForDisplay() as $item)

                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">

                                    <div class="flex items-start gap-4">

                                        <div class="mt-2 h-3 w-3 rounded-full bg-brand-500"></div>

                                        <div>
                                            <p class="font-medium leading-relaxed text-slate-800">
                                                {{ $item }}
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>
                    </div>
                </div>

                {{-- KTP --}}
                <div class="col-span-12 xl:col-span-4">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-slate-900">
                                KTP
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Dokumen identitas resmi
                            </p>
                        </div>

                        <div class="rounded-[28px] bg-slate-100 p-4">

                            <img
                                src="{{ route('admin.muthowif.ktp', $profile) }}"
                                alt="{{ __('admin.muthowif.ktp_alt') }}"
                                class="h-[260px] w-full rounded-2xl object-contain"
                            >

                        </div>

                    </div>
                </div>

            </div>

            {{-- ROW 3 --}}
            <div class="grid grid-cols-12 gap-6">

                {{-- BAHASA --}}
                <div class="col-span-12 lg:col-span-4">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900">
                                Bahasa
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Bahasa yang dikuasai
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">

                            @foreach ($profile->languagesForDisplay() as $item)

                                <span class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                                    {{ $item }}
                                </span>

                            @endforeach

                        </div>
                    </div>
                </div>

                {{-- PENDIDIKAN --}}
                <div class="col-span-12 lg:col-span-4">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900">
                                Pendidikan
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Riwayat pendidikan
                            </p>
                        </div>

                        <div class="space-y-4">

                            @foreach ($profile->educationsForDisplay() as $item)

                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">

                                    <div class="flex items-start gap-4">

                                        <div class="mt-2 h-3 w-3 rounded-full bg-brand-500"></div>

                                        <div>
                                            <p class="font-medium leading-relaxed text-slate-800">
                                                {{ $item }}
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>
                    </div>
                </div>

                {{-- DOKUMEN --}}
                <div class="col-span-12 lg:col-span-4">

                    <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-xl">

                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900">
                                Dokumen Pendukung
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                File tambahan pendukung
                            </p>
                        </div>

                        <div class="space-y-3">

                            @forelse ($profile->supportingDocuments as $doc)

                                <a
                                    href="{{ route('admin.muthowif.document', [$profile, $doc]) }}"
                                    target="_blank"
                                    class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 transition-all duration-300 hover:bg-slate-100"
                                >

                                    <span class="truncate text-sm font-medium text-slate-700">
                                        {{ $doc->original_name ?? basename($doc->path) }}
                                    </span>

                                    <span class="ml-4 text-sm font-semibold text-brand-700">
                                        Buka
                                    </span>

                                </a>

                            @empty

                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                                    Tidak ada dokumen pendukung.
                                </div>

                            @endforelse

                        </div>
                    </div>
                </div>

            </div>

            {{-- REJECT --}}
            @if ($profile->isPending())

                <div class="rounded-[32px] border border-rose-200 bg-white p-8 shadow-sm">

                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-rose-700">
                            Tolak Pendaftaran
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Berikan alasan penolakan registrasi
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.muthowif.reject', $profile) }}"
                        class="space-y-5"
                    >
                        @csrf

                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            rows="5"
                            class="block w-full rounded-3xl border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"
                            placeholder="{{ __('admin.muthowif.reject_placeholder') }}"
                        >{{ old('rejection_reason') }}</textarea>

                        <x-input-error :messages="$errors->get('rejection_reason')" />

                        <button
                            type="submit"
                            class="rounded-2xl bg-rose-600 px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:bg-rose-700 hover:shadow-xl"
                        >
                            {{ __('admin.muthowif.reject_submit') }}
                        </button>

                    </form>

                </div>

            @endif

        </div>
    </div>
</x-app-layout>