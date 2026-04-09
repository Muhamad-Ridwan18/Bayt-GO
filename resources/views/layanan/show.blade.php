@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;
    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
@endphp

<x-marketplace-layout :title="$profile->user->name">
    <div class="space-y-8 lg:space-y-10">
        <nav class="text-sm text-slate-500 flex items-center flex-wrap gap-1">
            <a href="{{ route('layanan.index', array_filter(request()->only(['start_date', 'end_date', 'q']))) }}" class="text-brand-700 hover:text-brand-800 font-medium">Cari muthowif</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <span class="text-slate-800 font-medium">{{ $profile->user->name }}</span>
        </nav>

        {{-- Profil muthowif: foto, identitas, bahasa, studi & pengalaman — satu kartu --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-market">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-50/80 via-white to-amber-50/40 pointer-events-none"></div>
            <div class="relative p-6 sm:p-8 flex flex-col sm:flex-row gap-6 sm:gap-8">
                <div class="shrink-0 mx-auto sm:mx-0">
                    <div class="relative">
                        <img
                            src="{{ route('layanan.photo', $profile) }}"
                            alt=""
                            class="h-36 w-36 sm:h-44 sm:w-44 rounded-3xl object-cover border-4 border-white shadow-lg ring-1 ring-slate-200/80 bg-slate-50"
                            onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22128%22 height=%22128%22/%3E%3Ctext x=%2250%25%22 y=%2255%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22%23475569%22%3E{{ mb_substr($profile->user->name, 0, 1) }}%3C/text%3E%3C/svg%3E'"
                        >
                        <span class="absolute -bottom-1 -right-1 rounded-full bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 shadow-md ring-2 ring-white">Terverifikasi</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1 text-center sm:text-left">
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ $profile->user->name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Pendamping umrah · layanan di Tanah Suci</p>
                    @if ($profile->languagesForDisplay() !== [])
                        <p class="mt-4 inline-flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            @foreach ($profile->languagesForDisplay() as $lang)
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1">{{ $lang }}</span>
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
            @if ($profile->educationsForDisplay() !== [] || $profile->workExperiencesForDisplay() !== [])
                <div class="relative border-t border-slate-200/70 px-6 sm:px-8 py-5 sm:py-6 space-y-5 bg-white/50 backdrop-blur-[2px]">
                    @if ($profile->educationsForDisplay() !== [])
                        <x-line-list label="Studi / pendidikan" :items="$profile->educationsForDisplay()" />
                    @endif
                    @if ($profile->workExperiencesForDisplay() !== [])
                        <x-line-list label="Pengalaman" :items="$profile->workExperiencesForDisplay()" />
                    @endif
                </div>
            @endif
        </div>

        {{-- Paket layanan (katalog) --}}
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Paket &amp; harga</h2>
            <p class="mt-1 text-sm text-slate-600">Pilih jenis layanan saat mengajukan booking di bawah.</p>
            <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
                @if ($group)
                    <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-market hover:border-brand-200/80 transition-all duration-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="inline-flex rounded-lg bg-brand-100 text-brand-800 text-xs font-bold px-2.5 py-1 uppercase tracking-wide">Group</span>
                                <h3 class="mt-3 text-lg font-semibold text-slate-900 group-hover:text-brand-800 transition-colors">Layanan group</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Jemaah rombongan</p>
                            </div>
                            @if ($group->daily_price !== null)
                                <div class="text-right shrink-0">
                                    <p class="text-xs text-slate-500">Mulai</p>
                                    <p class="text-lg font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}</p>
                                    <p class="text-xs text-slate-500">/ hari</p>
                                </div>
                            @endif
                        </div>
                        @if (filled($group->name))
                            <p class="mt-4 font-medium text-slate-800">{{ $group->name }}</p>
                        @else
                            <p class="mt-4 text-sm text-slate-500">Belum diisi oleh muthowif.</p>
                        @endif
                        @if ($group->min_pilgrims && $group->max_pilgrims)
                            <p class="mt-2 text-sm text-slate-600">{{ $group->min_pilgrims }}–{{ $group->max_pilgrims }} jemaah</p>
                        @endif
                        @if (filled($group->description))
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $group->description }}</p>
                        @endif
                        <ul class="mt-4 flex flex-wrap gap-2 text-xs">
                            @if (($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-slate-700 ring-1 ring-slate-200/80">Hotel sama: Rp {{ IndonesianNumber::formatThousands((string) (int) $group->same_hotel_price_per_day) }}/hari</span>
                            @endif
                            @if (($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-slate-700 ring-1 ring-slate-200/80">Transport: Rp {{ IndonesianNumber::formatThousands((string) (int) $group->transport_price_flat) }}</span>
                            @endif
                        </ul>
                    </article>
                @endif

                @if ($private)
                    <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-market hover:border-amber-200/80 transition-all duration-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="inline-flex rounded-lg bg-amber-100 text-amber-900 text-xs font-bold px-2.5 py-1 uppercase tracking-wide">Private</span>
                                <h3 class="mt-3 text-lg font-semibold text-slate-900 group-hover:text-amber-900 transition-colors">Layanan private</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Privat / keluarga</p>
                            </div>
                            @if ($private->daily_price !== null)
                                <div class="text-right shrink-0">
                                    <p class="text-xs text-slate-500">Mulai</p>
                                    <p class="text-lg font-bold text-amber-800">Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}</p>
                                    <p class="text-xs text-slate-500">/ hari</p>
                                </div>
                            @endif
                        </div>
                        @if (filled($private->name))
                            <p class="mt-4 font-medium text-slate-800">{{ $private->name }}</p>
                        @else
                            <p class="mt-4 text-sm text-slate-500">Belum diisi oleh muthowif.</p>
                        @endif
                        @if ($private->min_pilgrims && $private->max_pilgrims)
                            <p class="mt-2 text-sm text-slate-600">{{ $private->min_pilgrims }}–{{ $private->max_pilgrims }} jemaah</p>
                        @endif
                        @if (filled($private->description))
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $private->description }}</p>
                        @endif
                        <ul class="mt-4 flex flex-wrap gap-2 text-xs">
                            @if (($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-slate-700 ring-1 ring-slate-200/80">Hotel sama: Rp {{ IndonesianNumber::formatThousands((string) (int) $private->same_hotel_price_per_day) }}/hari</span>
                            @endif
                            @if (($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-slate-700 ring-1 ring-slate-200/80">Transport: Rp {{ IndonesianNumber::formatThousands((string) (int) $private->transport_price_flat) }}</span>
                            @endif
                        </ul>
                        @if ($private->addOns->isNotEmpty())
                            <div class="mt-5 border-t border-slate-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Extra add-on</p>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($private->addOns as $addon)
                                        <li class="flex items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-white px-3 py-2 ring-1 ring-amber-100/80">
                                            <span class="text-sm font-medium text-slate-800">{{ $addon->name }}</span>
                                            <span class="text-sm font-bold text-amber-900">Rp {{ IndonesianNumber::formatThousands((string) (int) $addon->price) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </article>
                @endif
            </div>
        </div>

        @include('layanan.partials.booking-panel', [
            'profile' => $profile,
            'group' => $group,
            'private' => $private,
            'bookingIntent' => $bookingIntent,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])

        <section class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/90 to-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Jadwal tidak tersedia (libur)</h2>
            <p class="mt-1 text-sm text-slate-600">Tanggal berikut muthowif tidak tersedia. Di luar itu, slot bisa sudah terisi — gunakan pencarian tanggal di daftar.</p>
            @if ($profile->blockedDates->isEmpty())
                <p class="mt-4 text-sm text-slate-600">Belum ada tanggal libur yang diumumkan (atau semua tanggal sudah lewat).</p>
            @else
                <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    @foreach ($profile->blockedDates as $bd)
                        <li class="rounded-xl bg-white/90 border border-amber-100 px-3 py-2 shadow-sm">
                            <span class="font-medium text-slate-900">{{ $bd->blocked_on->format('d/m/Y') }}</span>
                            @if (filled($bd->note))
                                <span class="block text-slate-500 text-xs mt-0.5">{{ $bd->note }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-marketplace-layout>
