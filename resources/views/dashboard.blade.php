<x-app-layout style="background-color: #650a0a;">
    <div class="py-8 sm:py-12" style="background-image: url('{{ asset('images/bg-01.jpeg') }}');">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (Auth::user()->isCustomer())
                {{-- Hero jamaah: teks penuh lebar + form pencarian lebar penuh (hindari kolom sempit) --}}
                <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 text-white shadow-market ring-1 ring-white/10">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                    <div class="absolute top-0 right-0 w-72 h-72 bg-brand-400/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
                    <div class="absolute bottom-0 left-1/4 w-96 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative px-5 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12 space-y-8">
                        <div class="max-w-3xl">
                            <p class="text-sm font-medium text-brand-100/90">Halo,</p>
                            <p class="text-2xl sm:text-4xl font-bold mt-1 tracking-tight">{{ Auth::user()->name }}</p>
                            <span class="mt-4 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-100 ring-1 ring-white/20">
                                {{ Auth::user()->role->label() }}
                            </span>
                            <h3 class="mt-6 text-xl sm:text-2xl font-semibold leading-snug text-white">
                                Temukan pendamping untuk tanggal perjalanan Anda
                            </h3>
                            <p class="mt-3 text-sm sm:text-base text-brand-100/90 leading-relaxed">
                                Marketplace hanya menampilkan muthowif <strong class="font-semibold text-white">tersedia</strong> — tidak bentrok libur atau booking lain pada rentang yang Anda pilih.
                            </p>
                        </div>

                        <div class="w-full min-w-0">
                            <p class="text-sm font-semibold text-brand-50 mb-3 flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                </span>
                                Cari ketersediaan
                            </p>
                            @include('layanan.partials.date-search-form', [
                                'startDate' => '',
                                'endDate' => '',
                                'searchQuery' => '',
                            ])
                        </div>

                        <div class="flex flex-wrap gap-2.5 sm:gap-3 pt-2 border-t border-white/10">
                            @foreach ([
                                'Muthowif terverifikasi',
                                'Slot & tanggal real-time',
                                'Group & private',
                            ] as $chip)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-2 text-xs font-medium text-white/95 ring-1 ring-white/15 backdrop-blur-sm">
                                    <svg class="h-3.5 w-3.5 text-emerald-300 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $chip }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </section>

                <a href="{{ route('bookings.index') }}" class="group flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-gradient-to-r from-white to-slate-50/90 p-5 shadow-sm ring-1 ring-slate-100 transition hover:border-brand-200 hover:shadow-md sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex gap-4 min-w-0">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-700 ring-1 ring-brand-200/80" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3m-6.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">Booking saya</p>
                            <p class="mt-0.5 text-sm text-slate-600 leading-relaxed">Lacak pengajuan, persetujuan muthowif, dan status perjalanan Anda.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center justify-center gap-1 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm group-hover:bg-brand-700 shrink-0 sm:px-5">
                        Buka daftar
                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                    </span>
                </a>
            @elseif (Auth::user()->isVerifiedMuthowif())
                @include('partials.dashboard-muthowif')
            @elseif (Auth::user()->isMuthowif())
                <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 sm:p-8 shadow-sm ring-1 ring-amber-100">
                    <div class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-amber-900">Akun muthowif sedang ditinjau</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">Halo, {{ Auth::user()->name }}</p>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                Tim admin akan memverifikasi dokumen Anda. Setelah disetujui, Anda mendapat akses penuh layanan, jadwal, dan saldo dompet.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 text-white p-6 sm:p-8 shadow-market">
                    <p class="text-sm font-medium text-brand-100">Halo,</p>
                    <p class="text-2xl font-bold mt-1">{{ Auth::user()->name }}</p>
                    <p class="mt-2 text-sm text-brand-100/90">
                        Anda masuk sebagai
                        <span class="font-semibold text-white">
                            {{ Auth::user()->role->label() }}
                        </span>
                    </p>
                </div>
            @endif

            @if (Auth::user()->isAdmin())
                <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-brand-950 to-brand-900 text-white p-6 shadow-market ring-1 ring-white/10 overflow-visible">
                    @php
                        $paidPaymentsBase = \App\Models\BookingPayment::query()->whereIn('status', ['settlement', 'capture']);
                        $totalPlatformFees = (float) (clone $paidPaymentsBase)->sum('platform_fee_amount');
                        $totalVolume = (int) (clone $paidPaymentsBase)->sum('gross_amount');
                        $settledCount = (int) (clone $paidPaymentsBase)->count();
                        $latestPayments = (clone $paidPaymentsBase)
                            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user'])
                            ->orderByDesc('settled_at')
                            ->limit(5)
                            ->get();
                        $pendingWithdrawCount = (int) \App\Models\MuthowifWithdrawal::query()
                            ->where('status', 'pending_approval')
                            ->count();
                        $fmt = fn (float|int $n) => \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $n));
                    @endphp

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                        <div class="lg:col-span-12">
                            <p class="text-sm font-medium text-emerald-200/90">Dashboard admin</p>
                            <h3 class="mt-1 text-2xl font-bold tracking-tight">
                                Pantau platform &amp; transaksi Midtrans
                            </h3>
                            <p class="mt-2 text-sm text-white/80 leading-relaxed">
                                Ringkasan pembayaran yang sudah settlement, termasuk biaya platform (15% total).
                            </p>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-2xl bg-white/10 border border-white/15 p-5 min-h-[8rem] flex flex-col items-center justify-center w-full text-center">
                                    <p class="text-[12px] font-semibold uppercase tracking-wide text-white/70 leading-4">Platform fee</p>
                                    <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums leading-tight">Rp {{ $fmt($totalPlatformFees) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 border border-white/15 p-5 min-h-[8rem] flex flex-col items-center justify-center w-full text-center">
                                    <p class="text-[12px] font-semibold uppercase tracking-wide text-white/70 leading-4">Volume bruto</p>
                                    <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums leading-tight">Rp {{ $fmt($totalVolume) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 border border-white/15 p-5 min-h-[8rem] flex flex-col items-center justify-center w-full text-center">
                                    <p class="text-[12px] font-semibold uppercase tracking-wide text-white/70 leading-4">Transaksi</p>
                                    <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums leading-tight">{{ $settledCount }}</p>
                                </div>
                            </div>
                        </div>

                            <div class="lg:col-span-12 w-full grid grid-cols-1 lg:grid-cols-12 gap-5 items-start min-w-0">
                            <div class="lg:col-span-8 w-full rounded-2xl bg-white text-slate-900 border border-white/20 min-w-0 overflow-visible flex flex-col">
                                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-slate-900">Transaksi terbaru</h4>
                                    <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600/10 px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-600/15">
                                        Lihat semua
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>

                                @if ($latestPayments->isEmpty())
                                    <p class="p-6 text-sm text-slate-500">Belum ada pembayaran settlement/capture.</p>
                                @else
                                    <div class="overflow-x-auto min-w-0 w-full">
                                        <table class="min-w-full text-sm table-fixed">
                                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                                <tr>
                                                    <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                                    <th class="px-4 py-3 whitespace-nowrap">Order</th>
                                                    <th class="px-4 py-3 whitespace-nowrap">Customer</th>
                                                    <th class="px-4 py-3 whitespace-nowrap">Muthowif</th>
                                                    <th class="px-4 py-3 text-right whitespace-nowrap">Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($latestPayments as $p)
                                                    @php $b = $p->muthowifBooking; @endphp
                                                    <tr class="hover:bg-slate-50/60">
                                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                                            {{ $p->settled_at?->format('d/m/Y H:i') ?? '—' }}
                                                        </td>
                                                        <td class="px-4 py-3 font-mono text-xs text-slate-700 truncate whitespace-nowrap" title="{{ $p->order_id }}">
                                                            {{ $p->order_id }}
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-800 truncate whitespace-nowrap">{{ $b?->customer?->name ?? '—' }}</td>
                                                        <td class="px-4 py-3 text-slate-800 truncate whitespace-nowrap">{{ $b?->muthowifProfile?->user?->name ?? '—' }}</td>
                                                        <td class="px-4 py-3 text-right font-medium text-brand-800 whitespace-nowrap">
                                                            Rp {{ $fmt((float) $p->platform_fee_amount) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                                <div class="lg:col-span-4 w-full space-y-4">
                                <div class="rounded-2xl bg-white/10 border border-white/15 p-5 overflow-visible">
                                    <h4 class="font-semibold text-white">Akses cepat</h4>
                                    <p class="mt-1 text-sm text-white/80 leading-relaxed break-words">
                                        Navigasi ke modul keuangan, verifikasi muthowif, dan log debugging webhook.
                                    </p>
                                    <p class="mt-2 text-xs text-white/70 leading-relaxed">
                                        Pending withdraw: <span class="font-semibold text-white">{{ $pendingWithdrawCount }}</span>
                                    </p>
                                    <div class="mt-4 flex flex-col gap-2">
                                        <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white text-slate-900 text-sm font-semibold hover:bg-slate-100 py-2.5">
                                            Keuangan
                                        </a>
                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.logs.index') }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-white/20 text-white text-sm font-semibold hover:bg-white/10 py-2.5">
                                                Logs
                                            </a>
                                            <a href="{{ route('admin.muthowif.index') }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-white/20 text-white text-sm font-semibold hover:bg-white/10 py-2.5">
                                                Verifikasi
                                            </a>
                                        </div>
                                        <a href="{{ route('admin.withdrawals.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 text-white text-sm font-semibold hover:bg-white/10 py-2.5">
                                            Withdraw
                                        </a>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-white/10 border border-white/15 p-5 overflow-visible">
                                    <h4 class="font-semibold text-white">Catatan biaya platform</h4>
                                    <p class="mt-1 text-sm text-white/80 leading-relaxed break-words">
                                        7,5% dari customer + 7,5% dari muthowif (total 15%). Saldo muthowif masuk saat layanan diselesaikan customer.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100/80">
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900">Langkah berikutnya</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                @if(Auth::user()->isAdmin())
                                    Gunakan menu <strong class="text-slate-800">Verifikasi muthowif</strong> untuk menyetujui atau menolak pendaftaran.
                                @elseif(Auth::user()->isCustomer())
                                    Isi tanggal di atas, lalu tinjau profil muthowif — harga, layanan, dan jadwal libur — sebelum mengajukan booking.
                                @elseif(Auth::user()->isVerifiedMuthowif())
                                    Atur pelayanan dari menu <strong class="text-slate-800">Pelayanan</strong>, lalu tanggapi permintaan dari jamaah.
                                @elseif(Auth::user()->isMuthowif())
                                    Setelah akun terverifikasi, Anda dapat menjelajahi permintaan terbuka dari jamaah.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100/80">
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-800" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">Profil akun</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                Nama, email, dan kata sandi dapat diubah kapan saja.
                            </p>
                            <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800">
                                Pengaturan profil
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
