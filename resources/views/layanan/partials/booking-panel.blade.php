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
    $oldAddOns = old('add_on_ids', []);
    if (! is_array($oldAddOns)) {
        $oldAddOns = [];
    }
    $selectedService = old('service_type', $defaultService);
    $addonsVisible = $selectedService === 'private';
    $oldWithSameHotel = old('with_same_hotel', false);
    $oldWithTransport = old('with_transport', false);
@endphp

<section class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-market">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%2314b8a6\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M0 40L40 0H20L0 20M40 40V20L20 40\'/%3E%3C/g%3E%3C/svg%3E')] opacity-60 pointer-events-none"></div>

    <div class="relative bg-gradient-to-r from-slate-900 via-brand-900 to-amber-950 px-6 py-5 sm:px-8 sm:py-6 sm:flex sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-200/90">Checkout</p>
            <h2 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Ajukan booking</h2>
            <p class="mt-1 text-sm text-brand-100/90 max-w-xl">Satu formulir — pilih paket, jumlah jemaah, dan add-on (private) lalu kirim permintaan.</p>
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
                <p class="text-sm text-slate-700">Masuk sebagai jamaah untuk mengajukan booking pada profil ini.</p>
                <a href="{{ route('login.intended', ['next' => request()->getRequestUri()]) }}"
                   class="mt-4 inline-flex justify-center items-center px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold shadow-md hover:bg-brand-700">
                    Masuk untuk booking
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-2 block text-sm font-medium text-brand-700 hover:text-brand-800">Belum punya akun? Daftar</a>
                @endif
            </div>
        @elseif ($intent['reason'] === 'not_customer')
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                Hanya akun <strong>jamaah</strong> yang dapat mengajukan booking lewat fitur ini.
            </p>
        @elseif ($intent['reason'] === 'missing_dates')
            <p class="text-sm text-slate-700 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
                Tambahkan <strong>tanggal mulai</strong> (dan selesai jika perlu) lewat halaman
                <a href="{{ route('layanan.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Cari muthowif</a>,
                lalu buka kembali profil ini dari hasil pencarian.
            </p>
        @elseif ($intent['reason'] === 'invalid_dates')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                Tanggal di URL tidak valid. Perbaiki di halaman pencarian.
            </p>
        @elseif ($intent['reason'] === 'past_start')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                Tanggal mulai tidak boleh di masa lalu. Ubah tanggal di pencarian.
            </p>
        @elseif ($intent['reason'] === 'range_too_long')
            <p class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-2xl px-4 py-3">
                Rentang tanggal melebihi batas yang diizinkan. Perpendek rentang di pencarian.
            </p>
        @elseif ($intent['reason'] === 'slot_unavailable')
            <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                Untuk rentang <strong>{{ $rangeLabel }}</strong>, slot ini tidak tersedia (libur atau sudah terisi). Ubah tanggal di
                <a href="{{ route('layanan.index', array_filter(['start_date' => $startDate, 'end_date' => $endDate ?? null])) }}" class="font-semibold underline">Cari muthowif</a>.
            </p>
        @elseif ($intent['can_submit'])
            @if (! $group && ! $private)
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                    Layanan muthowif belum dikonfigurasi. Tidak dapat mengajukan booking.
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Periode menginap</p>
                            <p class="mt-0.5 text-base font-semibold text-slate-900">{{ $rangeLabel }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-brand-50 text-brand-800 text-xs font-semibold px-3 py-1 ring-1 ring-brand-200/80">Slot tersedia</span>
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
                            <legend class="text-sm font-semibold text-slate-900">Pilih paket</legend>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @if ($group)
                                    <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-brand-300 hover:shadow-md has-[:checked]:border-brand-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-brand-50 has-[:checked]:to-white has-[:checked]:shadow-md">
                                        <input type="radio" name="service_type" value="group" class="sr-only peer"
                                               x-model="serviceType"
                                               @checked(old('service_type', $defaultService) === 'group')>
                                        <span class="flex flex-col gap-1">
                                            <span class="inline-flex w-fit rounded-lg bg-brand-100 text-brand-800 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5">Group</span>
                                            <span class="font-semibold text-slate-900">Jemaah group</span>
                                            @if ($group->daily_price !== null)
                                                <span class="text-sm text-slate-600">Mulai <span class="font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}</span>/hari</span>
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
                                            <span class="font-semibold text-slate-900">Jemaah private</span>
                                            @if ($private->daily_price !== null)
                                                <span class="text-sm text-slate-600">Mulai <span class="font-bold text-amber-800">Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}</span>/hari</span>
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
                            <label for="pilgrim_count" class="text-sm font-semibold text-slate-900">Jumlah jemaah</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <input type="number" name="pilgrim_count" id="pilgrim_count" required
                                       min="1"
                                       class="block w-28 rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-center py-2.5"
                                       value="{{ $defaultPilgrim }}"
                                       x-bind:min="currentBounds().min"
                                       x-bind:max="currentBounds().max">
                                <span class="text-sm text-slate-500">orang</span>
                            </div>
                            @if ($group)
                                <p class="mt-2 text-xs text-slate-500" x-show="serviceType === 'group'">
                                    Kuota group: {{ $gBounds['min'] }}–{{ $gBounds['max'] }} orang.
                                </p>
                            @endif
                            @if ($private)
                                <p class="mt-2 text-xs text-slate-500" x-show="serviceType === 'private'">
                                    Kuota private: {{ $pBounds['min'] }}–{{ $pBounds['max'] }} orang.
                                </p>
                            @endif
                        </div>

                        @if ($private)
                            <div
                                id="booking-addon-box-{{ $profile->id }}"
                                data-booking-addon-box
                                class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/60 via-white to-white p-5 shadow-inner {{ $addonsVisible ? '' : 'hidden' }}"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">Tambahan layanan</h3>
                                        <p class="mt-0.5 text-xs text-slate-600">Centang seperti memilih di keranjang — ikut dalam pengajuan ini.</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-100 text-amber-900 text-[10px] font-bold uppercase px-2 py-1">Private</span>
                                </div>
                                @if ($private->addOns->isEmpty())
                                    <p class="mt-4 text-sm text-slate-500">Belum ada add-on untuk paket ini.</p>
                                @else
                                    <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3" data-booking-addon-list>
                                        @foreach ($private->addOns as $addon)
                                            <li>
                                                <label class="relative flex h-full cursor-pointer flex-col rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition hover:border-amber-300 hover:shadow-md has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:shadow-md has-[:disabled]:opacity-45 has-[:disabled]:cursor-not-allowed">
                                                    <input
                                                        type="checkbox"
                                                        name="add_on_ids[]"
                                                        value="{{ $addon->id }}"
                                                        data-booking-addon-cb
                                                        class="absolute right-3 top-3 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                        @disabled(! $addonsVisible)
                                                        @checked(in_array($addon->id, $oldAddOns, true))
                                                    >
                                                    <span class="pr-8 text-sm font-semibold text-slate-900 leading-snug">{{ $addon->name }}</span>
                                                    <span class="mt-2 text-lg font-bold text-amber-900">Rp {{ IndonesianNumber::formatThousands((string) (int) $addon->price) }}</span>
                                                    <span class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-400">per item</span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                            <h3 class="text-sm font-bold text-slate-900">Opsi tambahan</h3>

                            @if ($group)
                                <div x-show="serviceType === 'group'" class="space-y-2">
                                    @if (($group->same_hotel_price_per_day ?? null) !== null && (float) $group->same_hotel_price_per_day > 0)
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="with_same_hotel" value="1"
                                                class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                x-bind:disabled="serviceType !== 'group'"
                                                @checked($oldWithSameHotel)>
                                            <span class="text-sm text-slate-700">Muthowif tinggal di hotel yang sama (+Rp {{ IndonesianNumber::formatThousands((string) (int) $group->same_hotel_price_per_day) }}/hari)</span>
                                        </label>
                                    @endif
                                    @if (($group->transport_price_flat ?? null) !== null && (float) $group->transport_price_flat > 0)
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="with_transport" value="1"
                                                class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                x-bind:disabled="serviceType !== 'group'"
                                                @checked($oldWithTransport)>
                                            <span class="text-sm text-slate-700">Termasuk transportasi (+Rp {{ IndonesianNumber::formatThousands((string) (int) $group->transport_price_flat) }})</span>
                                        </label>
                                    @endif
                                </div>
                            @endif

                            @if ($private)
                                <div x-show="serviceType === 'private'" class="space-y-2">
                                    @if (($private->same_hotel_price_per_day ?? null) !== null && (float) $private->same_hotel_price_per_day > 0)
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="with_same_hotel" value="1"
                                                class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                x-bind:disabled="serviceType !== 'private'"
                                                @checked($oldWithSameHotel)>
                                            <span class="text-sm text-slate-700">Muthowif tinggal di hotel yang sama (+Rp {{ IndonesianNumber::formatThousands((string) (int) $private->same_hotel_price_per_day) }}/hari)</span>
                                        </label>
                                    @endif
                                    @if (($private->transport_price_flat ?? null) !== null && (float) $private->transport_price_flat > 0)
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="with_transport" value="1"
                                                class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                                x-bind:disabled="serviceType !== 'private'"
                                                @checked($oldWithTransport)>
                                            <span class="text-sm text-slate-700">Termasuk transportasi (+Rp {{ IndonesianNumber::formatThousands((string) (int) $private->transport_price_flat) }})</span>
                                        </label>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2 border-t border-slate-200">
                            <p class="text-xs text-slate-500 max-w-md">
                                Dengan mengajukan, Anda setuju muthowif akan meninjau permintaan (status <strong>Menunggu</strong>).
                            </p>
                            <button type="submit"
                                    class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3.5 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 text-white text-sm font-bold shadow-lg shadow-brand-900/20 hover:from-brand-500 hover:to-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition">
                                Ajukan booking
                            </button>
                        </div>
                    </form>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const form = document.getElementById('booking-form-{{ $profile->id }}');
                        const box = document.getElementById('booking-addon-box-{{ $profile->id }}');
                        if (! form || ! box) {
                            return;
                        }
                        const radios = form.querySelectorAll('input[name="service_type"]');
                        function syncBookingAddons() {
                            const checked = form.querySelector('input[name="service_type"]:checked');
                            const isPrivate = checked && checked.value === 'private';
                            box.classList.toggle('hidden', ! isPrivate);
                            box.querySelectorAll('[data-booking-addon-cb]').forEach(function (cb) {
                                cb.disabled = ! isPrivate;
                                if (! isPrivate) {
                                    cb.checked = false;
                                }
                            });
                        }
                        radios.forEach(function (r) {
                            r.addEventListener('change', syncBookingAddons);
                        });
                        syncBookingAddons();
                    });
                </script>
            @endif
        @endif
    </div>
</section>
