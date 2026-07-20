@php
    use App\Enums\AffiliateBankVerificationStatus;
    use App\Enums\AffiliateCommissionStatus;
    use App\Enums\AffiliateWithdrawalStatus;
    use App\Support\AffiliateBankOptions;
    use App\Support\IndonesianNumber;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $firstName = str(auth()->user()->name)->before(' ');
    $shareText = 'Booking muthowif di BaytGo pakai link affiliate saya: '.$shareUrl;
    $mom = $stats['mom'];
    $ratePct = rtrim(rtrim(number_format($stats['rate'] * 100, 2, '.', ''), '0'), '.');
    $levelProgress = (float) $stats['level_progress'];
    $remainingToNext = $stats['remaining_to_next'];

    $deltaLabel = function (float|int $delta, bool $money = true, bool $pct = false): array {
        $up = $delta >= 0;
        $abs = abs($delta);
        if ($pct) {
            $text = ($up ? '▲ ' : '▼ ').number_format($abs, $abs == (int) $abs ? 0 : 1, ',', '.').'% dari bulan lalu';
        } elseif ($money) {
            $text = ($up ? '▲ Rp' : '▼ Rp').IndonesianNumber::formatThousands((string) (int) round($abs)).' dari bulan lalu';
        } else {
            $text = ($up ? '▲ ' : '▼ ').((int) $abs).' dari bulan lalu';
        }

        return ['text' => $text, 'up' => $up];
    };

    $chartW = 600;
    $chartH = 180;
    $padX = 8;
    $padY = 12;
    $amountCount = count($chart['amounts']);
    $chartMax = max(1, (int) $chart['max_amount']);
    $linePts = [];
    $areaPts = [];
    for ($i = 0; $i < $amountCount; $i++) {
        $x = $padX + ($amountCount <= 1 ? 0 : ($i / ($amountCount - 1)) * ($chartW - 2 * $padX));
        $y = ($chartH - $padY) - (((float) $chart['amounts'][$i]) / $chartMax) * ($chartH - 2 * $padY);
        $linePts[] = round($x, 2).','.round($y, 2);
        if ($i === 0) {
            $areaPts[] = round($x, 2).','.($chartH - $padY);
        }
        $areaPts[] = round($x, 2).','.round($y, 2);
        if ($i === $amountCount - 1) {
            $areaPts[] = round($x, 2).','.($chartH - $padY);
        }
    }
    $linePoly = implode(' ', $linePts);
    $areaPoly = implode(' ', $areaPts);
    $labelStep = max(1, (int) floor(($amountCount - 1) / 4));

    $commissionBadge = fn (AffiliateCommissionStatus $s) => match ($s) {
        AffiliateCommissionStatus::Available => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        AffiliateCommissionStatus::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
        AffiliateCommissionStatus::Void => 'bg-red-50 text-red-700 ring-red-200',
    };
    $withdrawalBadge = fn (AffiliateWithdrawalStatus $s) => match ($s) {
        AffiliateWithdrawalStatus::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        AffiliateWithdrawalStatus::Approved => 'bg-blue-50 text-blue-700 ring-blue-200',
        AffiliateWithdrawalStatus::Requested => 'bg-amber-50 text-amber-700 ring-amber-200',
        AffiliateWithdrawalStatus::Rejected, AffiliateWithdrawalStatus::Failed => 'bg-red-50 text-red-700 ring-red-200',
    };

    $primaryBank = $bankAccounts->firstWhere('is_primary', true) ?? $bankAccounts->first();
    $commissionDelta = $deltaLabel((float) $mom['commission_delta']);
    $clicksDelta = $deltaLabel((float) $mom['clicks_delta_pct'], false, true);
    $balanceDelta = $deltaLabel((float) $mom['balance_delta']);
    $pendingDelta = $deltaLabel((float) $mom['pending_delta']);
    $bookingDelta = $deltaLabel((int) $mom['booking_delta'], false);
    $withdrawDelta = $deltaLabel((float) $mom['withdraw_delta']);
@endphp
<x-app-layout>
    <div
        class="ui-page-y bg-slate-50/80"
        x-data="{
            copied: null,
            showBenefits: false,
            copy(text, which) {
                navigator.clipboard.writeText(text);
                this.copied = which;
                setTimeout(() => { if (this.copied === which) this.copied = null; }, 1600);
            },
            share() {
                if (navigator.share) {
                    navigator.share({ title: 'BaytGo Affiliate', text: @js($shareText), url: @js($shareUrl) }).catch(() => {});
                } else {
                    this.copy(@js($shareUrl), 'share');
                }
            },
        }"
    >
        <x-page-container class="ui-stack-compact">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            {{-- Hero --}}
            <section class="relative overflow-hidden rounded-[1.75rem] bg-[#0B5C4B] text-white shadow-lg shadow-emerald-900/10">
                <div class="pointer-events-none absolute inset-0 opacity-[0.12]" aria-hidden="true" style="background-image: radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px), radial-gradient(circle at 80% 60%, #fff 1px, transparent 1px); background-size: 28px 28px;"></div>
                <div class="pointer-events-none absolute -right-16 -top-20 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>

                <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-12 lg:gap-8 lg:p-8">
                    <div class="lg:col-span-7">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ $firstName }}! 👋</h1>
                        <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-emerald-100/85">
                            Terus bagikan link affiliate kamu dan dapatkan komisi lebih banyak.
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 backdrop-blur-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-200/90">Kode Affiliate</p>
                                <button type="button" @click="copy(@js($affiliate->code), 'code')" class="mt-1.5 flex w-full items-center justify-between gap-2 text-left">
                                    <span class="font-mono text-lg font-bold tracking-wider">{{ $affiliate->code }}</span>
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-emerald-100">
                                        <svg x-show="copied !== 'code'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                        <svg x-show="copied === 'code'" x-cloak class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </span>
                                </button>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 backdrop-blur-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-200/90">Link Affiliate</p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <p class="min-w-0 flex-1 truncate font-mono text-xs text-emerald-50 sm:text-sm">{{ $shareUrlDisplay }}</p>
                                    <button type="button" @click="copy(@js($shareUrl), 'link')" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-100" title="Salin link">
                                        <svg x-show="copied !== 'link'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                        <svg x-show="copied === 'link'" x-cloak class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </button>
                                </div>
                                <button type="button" @click="share()" class="mt-2.5 hidden w-full items-center justify-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 sm:inline-flex">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                    Bagikan Link
                                </button>
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-200/90">Total Komisi</p>
                            <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight sm:text-4xl">Rp{{ $fmt($stats['total_commission']) }}</p>
                            <p @class(['mt-1 text-xs font-medium', $commissionDelta['up'] ? 'text-emerald-200' : 'text-amber-200'])>{{ $commissionDelta['text'] }}</p>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="h-full rounded-2xl bg-black/20 p-4 ring-1 ring-white/10 backdrop-blur-sm sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-200/80">Progress Level</p>
                                    <div class="mt-2 flex items-center gap-2.5">
                                        <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/25 text-sm font-bold text-white ring-1 ring-emerald-300/40">
                                            <svg class="absolute inset-1 text-emerald-200/80" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l7 3v6c0 5-3.5 9.4-7 11-3.5-1.6-7-6-7-11V5l7-3z"/></svg>
                                            <span class="relative">{{ $stats['level'] }}</span>
                                        </span>
                                        <div>
                                            <p class="text-xl font-bold">{{ $stats['level_label'] }}</p>
                                            <p class="text-xs text-emerald-100/75">Rate {{ $ratePct }}%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($stats['next_min'] !== null)
                                <div class="mt-4">
                                    <div class="mb-1.5 flex justify-between text-xs">
                                        <span class="font-medium text-emerald-100/90">{{ number_format($levelProgress, 0) }}% menuju Level {{ $stats['level'] + 1 }}</span>
                                    </div>
                                    <div class="h-2.5 overflow-hidden rounded-full bg-white/15">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-300 to-yellow-400" style="width: {{ number_format($levelProgress, 2, '.', '') }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs leading-relaxed text-emerald-100/80">
                                        Butuh omzet
                                        <span class="font-semibold text-white">Rp {{ $fmt($remainingToNext ?? 0) }}</span>
                                        lagi untuk naik ke Level {{ $stats['level'] + 1 }}.
                                    </p>
                                </div>
                            @else
                                <p class="mt-4 text-sm font-medium text-emerald-100">Anda sudah di level tertinggi.</p>
                            @endif

                            <button type="button" @click="showBenefits = true" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-white/25 bg-white/5 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                                Lihat Benefit Level
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-12 lg:hidden">
                        <button type="button" @click="share()" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3.5 text-sm font-bold text-emerald-900 shadow-sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                            Bagikan Link Affiliate
                        </button>
                    </div>
                </div>
            </section>

            {{-- Stats --}}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Total Klik', 'value' => (string) $stats['total_clicks'], 'delta' => $clicksDelta, 'icon' => 'click', 'tone' => 'sky'],
                    ['label' => 'Saldo Komisi', 'value' => 'Rp'.$fmt($stats['available_balance']), 'delta' => $balanceDelta, 'icon' => 'wallet', 'tone' => 'emerald'],
                    ['label' => 'Pending Komisi', 'value' => 'Rp'.$fmt($stats['pending_commission']), 'delta' => $pendingDelta, 'icon' => 'clock', 'tone' => 'amber'],
                    ['label' => 'Booking Berhasil', 'value' => (string) $stats['success_booking'], 'delta' => $bookingDelta, 'icon' => 'check', 'tone' => 'blue'],
                    ['label' => 'Total Withdraw', 'value' => 'Rp'.$fmt($stats['total_withdraw']), 'delta' => $withdrawDelta, 'icon' => 'bank', 'tone' => 'violet'],
                ] as $card)
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <span @class([
                                'flex h-9 w-9 items-center justify-center rounded-full ring-1',
                                'bg-sky-50 text-sky-600 ring-sky-100' => $card['tone'] === 'sky',
                                'bg-emerald-50 text-emerald-600 ring-emerald-100' => $card['tone'] === 'emerald',
                                'bg-amber-50 text-amber-600 ring-amber-100' => $card['tone'] === 'amber',
                                'bg-blue-50 text-blue-600 ring-blue-100' => $card['tone'] === 'blue',
                                'bg-violet-50 text-violet-600 ring-violet-100' => $card['tone'] === 'violet',
                            ])>
                                @if ($card['icon'] === 'click')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672Z" /></svg>
                                @elseif ($card['icon'] === 'wallet')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" /></svg>
                                @elseif ($card['icon'] === 'clock')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                @elseif ($card['icon'] === 'check')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                @endif
                            </span>
                        </div>
                        <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">{{ $card['value'] }}</p>
                        <p @class(['mt-1 text-[11px] font-medium', $card['delta']['up'] ? 'text-emerald-600' : 'text-amber-600'])>{{ $card['delta']['text'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Chart + Tips --}}
            <div class="grid gap-4 xl:grid-cols-5">
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-slate-900">Statistik Komisi</h2>
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">30 Hari Terakhir</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <div>
                            <p class="text-xs text-slate-500">Total Komisi</p>
                            <p class="text-xl font-bold tabular-nums text-slate-900">Rp{{ $fmt($chart['total_amount']) }}</p>
                        </div>
                        <p @class(['text-xs font-semibold', $commissionDelta['up'] ? 'text-emerald-600' : 'text-amber-600'])>{{ $commissionDelta['text'] }}</p>
                    </div>
                    <div class="mt-4">
                        <svg class="h-44 w-full" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" role="img" aria-label="Grafik komisi 30 hari">
                            <defs>
                                <linearGradient id="affChartFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.28" />
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.02" />
                                </linearGradient>
                            </defs>
                            <polygon points="{{ $areaPoly }}" fill="url(#affChartFill)" />
                            <polyline points="{{ $linePoly }}" fill="none" stroke="#059669" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        </svg>
                        <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                            @foreach ($chart['labels'] as $i => $label)
                                @if ($i % $labelStep === 0 || $i === $amountCount - 1)
                                    <span>{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
                    <h2 class="text-base font-bold text-slate-900">Tips Meningkatkan Komisi</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ([
                            ['title' => 'Bagikan link ke lebih banyak orang', 'desc' => 'Kirim ke grup WhatsApp, komunitas, dan keluarga.'],
                            ['title' => 'Promosikan di media sosial', 'desc' => 'WhatsApp Status, Instagram, Facebook, TikTok.'],
                            ['title' => 'Rekomendasikan layanan terbaik', 'desc' => 'Bantu jamaah pilih muthowif yang cocok.'],
                            ['title' => 'Konsisten setiap hari', 'desc' => 'Semakin aktif share, semakin besar peluang komisi.'],
                        ] as $tip)
                            <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-800">{{ $tip['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $tip['desc'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            {{-- Bank + Withdraw + Commission history --}}
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" x-data="{ addBank: {{ $bankAccounts->isEmpty() ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-base font-bold text-slate-900">Rekening Pencairan</h2>
                        <button type="button" @click="addBank = !addBank" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">+ Tambah Rekening</button>
                    </div>

                    @if ($primaryBank)
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900">{{ $primaryBank->bank_name }}</p>
                                    <p class="font-mono text-sm tracking-wide text-slate-600">**** {{ substr((string) $primaryBank->account_number, -4) }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $primaryBank->account_holder }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-slate-50 px-3 py-3 text-xs text-slate-600 ring-1 ring-slate-200">Belum ada rekening. Tambahkan untuk menarik komisi.</p>
                    @endif

                    @if ($bankAccounts->count() > 1)
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($bankAccounts as $bank)
                                @continue($primaryBank && $bank->is($primaryBank))
                                <li class="flex items-center justify-between gap-2 py-2 text-sm">
                                    <span class="truncate text-slate-700">{{ $bank->bank_name }} · **** {{ substr((string) $bank->account_number, -4) }}</span>
                                    <form method="POST" action="{{ route('affiliate.bank-accounts.destroy', $bank) }}" onsubmit="return confirm('Hapus rekening ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600">Hapus</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('affiliate.bank-accounts.store') }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4" x-show="addBank" x-cloak>
                        @csrf
                        <div>
                            <x-input-label for="bank_code" value="Bank" />
                            <select id="bank_code" name="bank_code" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                @foreach (AffiliateBankOptions::all() as $code => $label)
                                    <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="account_holder" value="Nama pemilik" />
                            <x-text-input id="account_holder" name="account_holder" class="mt-1 block w-full" :value="old('account_holder')" required />
                        </div>
                        <div>
                            <x-input-label for="account_number" value="Nomor rekening" />
                            <x-text-input id="account_number" name="account_number" class="mt-1 block w-full" :value="old('account_number')" required />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_primary" value="1" class="rounded border-slate-300"> Jadikan primary
                        </label>
                        <x-submit-button class="inline-flex w-full items-center justify-center rounded-xl bg-[#0B5C4B] px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900">
                            Simpan Rekening
                        </x-submit-button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" x-data="{ amount: @js((string) old('amount', '')) }">
                    <h2 class="text-base font-bold text-slate-900">Request Withdraw</h2>
                    <div class="mt-4 rounded-2xl bg-emerald-50/80 px-4 py-3 ring-1 ring-emerald-100">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Saldo Komisi Tersedia</p>
                        <p class="mt-0.5 text-2xl font-bold tabular-nums text-emerald-900">Rp{{ $fmt($stats['available_balance']) }}</p>
                    </div>
                    <form method="POST" action="{{ route('affiliate.withdrawals.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="amount" value="Nominal Withdraw" />
                            <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" x-model="amount" min="1" step="1" required placeholder="Masukkan nominal" />
                            <p class="mt-1 text-[11px] text-slate-500">Minimal withdraw Rp{{ $fmt($stats['min_withdraw']) }}</p>
                            <x-input-error :messages="$errors->get('amount')" />
                        </div>
                        <div>
                            <x-input-label for="bank_account_id" value="Pilih Rekening Tujuan" />
                            <select id="bank_account_id" name="bank_account_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                <option value="">Pilih rekening</option>
                                @foreach ($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->bank_name }} · **** {{ substr((string) $bank->account_number, -4) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Catatan (opsional)" />
                            <x-text-input id="notes" name="notes" class="mt-1 block w-full" :value="old('notes')" />
                        </div>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0B5C4B] px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-900">
                            Ajukan Withdraw
                        </button>
                    </form>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2 xl:col-span-1">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Riwayat Komisi</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Keterangan</th>
                                    <th class="px-4 py-2.5 text-right">Nominal</th>
                                    <th class="px-4 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($commissions as $commission)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $commission->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-800">Booking #{{ $commission->booking?->booking_code ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">Rp{{ $fmt((float) $commission->commission_amount) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $commissionBadge($commission->status) }}">{{ $commission->status->label() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada komisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($commissions->hasPages())
                        <div class="border-t border-slate-100 px-4 py-3">{{ $commissions->links() }}</div>
                    @endif
                </section>
            </div>

            {{-- Withdraw history --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-slate-900">Riwayat Withdraw</h2>
                </div>
                @if ($withdrawals->isEmpty())
                    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" /></svg>
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-800">Belum ada riwayat withdraw</p>
                        <p class="mt-1 max-w-sm text-xs text-slate-500">Ajukan penarikan setelah saldo komisi mencapai minimal withdraw.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Waktu</th>
                                    <th class="px-4 py-3 text-right">Nominal</th>
                                    <th class="px-4 py-3">Rekening</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($withdrawals as $withdrawal)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $withdrawal->requested_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">Rp{{ $fmt((float) $withdrawal->amount) }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $withdrawal->beneficiary_bank }} · **** {{ substr((string) $withdrawal->beneficiary_account, -4) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $withdrawalBadge($withdrawal->status) }}">{{ $withdrawal->status->label() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($withdrawals->hasPages())
                        <div class="border-t border-slate-100 px-4 py-3">{{ $withdrawals->links() }}</div>
                    @endif
                @endif
            </section>

            {{-- Ledger (compact) --}}
            <details class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <summary class="cursor-pointer list-none border-b border-slate-100 px-5 py-4 font-bold text-slate-900 sm:px-6">
                    Ledger Wallet
                    <span class="ml-2 text-xs font-normal text-slate-500">tampilkan detail</span>
                </summary>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ledger as $entry)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $entry->occurred_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $entry->type->label() }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ (float) $entry->amount >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                        {{ (float) $entry->amount >= 0 ? '+' : '−' }}Rp{{ $fmt(abs((float) $entry->amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-900">Rp{{ $fmt((float) $entry->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada transaksi wallet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($ledger->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $ledger->links() }}</div>
                @endif
            </details>
        </x-page-container>

        {{-- Benefits modal --}}
        <div
            x-show="showBenefits"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center"
            @keydown.escape.window="showBenefits = false"
        >
            <div class="absolute inset-0" @click="showBenefits = false"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Benefit Level Affiliate</h3>
                        <p class="mt-1 text-sm text-slate-500">Rate komisi naik otomatis mengikuti total omzet beratribusi.</p>
                    </div>
                    <button type="button" @click="showBenefits = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <ul class="mt-5 space-y-3">
                    @foreach ($stats['tiers'] as $tier)
                        @php $tierRate = rtrim(rtrim(number_format($tier['rate'] * 100, 2, '.', ''), '0'), '.'); @endphp
                        <li @class([
                            'flex items-center justify-between rounded-xl px-4 py-3 ring-1',
                            'bg-emerald-50 ring-emerald-200' => $tier['level'] === $stats['level'],
                            'bg-slate-50 ring-slate-200' => $tier['level'] !== $stats['level'],
                        ])>
                            <div>
                                <p class="font-bold text-slate-900">Level {{ $tier['level'] }}</p>
                                <p class="text-xs text-slate-500">
                                    @if ($tier['min'] > 0)
                                        Omzet ≥ Rp {{ $fmt($tier['min']) }}
                                    @else
                                        Level awal
                                    @endif
                                </p>
                            </div>
                            <p class="text-lg font-bold tabular-nums text-emerald-700">{{ $tierRate }}%</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
