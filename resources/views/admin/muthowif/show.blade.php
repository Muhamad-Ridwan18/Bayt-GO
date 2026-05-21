<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8">
        <div class="max-w-[1600px] mx-auto px-4 lg:px-8 space-y-6">

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
            <div
                class="rounded-[32px] border border-slate-200 bg-gradient-to-r from-white to-slate-50 p-7 shadow-sm"
            >
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">

                    <div class="flex items-center gap-5">
                        <img
                            src="{{ route('admin.muthowif.photo', $profile) }}"
                            alt="{{ __('admin.muthowif.photo_alt') }}"
                            class="h-28 w-28 rounded-[28px] object-cover border border-slate-200 shadow-sm"
                        >

                        <div>
                            <p class="text-sm text-slate-500">
                                {{ __('admin.muthowif.name') }}
                            </p>

                            <h1 class="text-3xl font-bold text-slate-900">
                                {{ $profile->user->name }}
                            </h1>

                            <p class="mt-1 text-slate-600">
                                {{ $profile->user->email }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-3">

                                <span class="rounded-full px-4 py-2 text-sm font-semibold
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

                                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-700">
                                    {{ $profile->birth_date?->translatedFormat('d M Y') }}
                                </span>

                            </div>
                        </div>
                    </div>

                    @if ($profile->isPending())
                        <form
                            method="POST"
                            action="{{ route('admin.muthowif.approve', $profile) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg"
                            >
                                {{ __('admin.muthowif.approve_registration') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- BIODATA FULL WIDTH --}}
            <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm">

                <div class="mb-8">
                    <h2 class="text-xl font-bold text-slate-900">
                        Biodata
                    </h2>

                    <p class="text-sm text-slate-500">
                        Informasi lengkap muthowif
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                    <div>
                        <p class="text-sm text-slate-400 mb-1">
                            WhatsApp
                        </p>

                        <p class="font-semibold text-slate-900">
                            {{ $profile->phone }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-400 mb-1">
                            Tanggal Lahir
                        </p>

                        <p class="font-semibold text-slate-900">
                            {{ $profile->birth_date?->translatedFormat('d M Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-400 mb-1">
                            NIK
                        </p>

                        <p class="font-semibold text-slate-900">
                            {{ $profile->nik }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-400 mb-1">
                            Passport
                        </p>

                        <p class="font-semibold text-slate-900">
                            {{ $profile->passport_number ?? '—' }}
                        </p>
                    </div>

                    <div class="xl:col-span-4">
                        <p class="text-sm text-slate-400 mb-1">
                            Alamat
                        </p>

                        <p class="font-semibold text-slate-900 whitespace-pre-line">
                            {{ $profile->address }}
                        </p>
                    </div>

                </div>
            </div>

            {{-- CONTENT GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">

                {{-- FOTO --}}
                <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-xl">
                    <h2 class="text-lg font-bold mb-5">
                        Foto Profil
                    </h2>

                    <img
                        src="{{ route('admin.muthowif.photo', $profile) }}"
                        class="w-full aspect-[3/4] rounded-[28px] object-cover bg-slate-100"
                    />
                </div>

                {{-- KTP --}}
                <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-xl">
                    <h2 class="text-lg font-bold mb-5">
                        KTP
                    </h2>

                    <img
                        src="{{ route('admin.muthowif.ktp', $profile) }}"
                        class="w-full aspect-video rounded-[28px] object-cover bg-slate-100"
                    />
                </div>

                {{-- BAHASA --}}
                <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold mb-5">
                        Bahasa
                    </h2>

                    <div class="flex flex-wrap gap-3">
                        @foreach ($profile->languagesForDisplay() as $item)
                            <span class="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700">
                                {{ $item }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- PENDIDIKAN --}}
                <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold mb-5">
                        Pendidikan
                    </h2>

                    <div class="space-y-3">
                        @foreach ($profile->educationsForDisplay() as $item)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                {{ $item }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- PENGALAMAN --}}
                <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold mb-5">
                        Pengalaman Kerja
                    </h2>

                    <div class="space-y-3">
                        @foreach ($profile->workExperiencesForDisplay() as $item)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                {{ $item }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- DOKUMEN --}}
                @if ($profile->supportingDocuments->isNotEmpty())
                    <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold mb-5">
                            Dokumen Pendukung
                        </h2>

                        <div class="space-y-3">
                            @foreach ($profile->supportingDocuments as $doc)
                                <a
                                    href="{{ route('admin.muthowif.document', [$profile, $doc]) }}"
                                    target="_blank"
                                    class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-4 hover:bg-slate-100 transition"
                                >
                                    <span class="truncate">
                                        {{ $doc->original_name ?? basename($doc->path) }}
                                    </span>

                                    <span class="font-semibold text-brand-700">
                                        Buka
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>