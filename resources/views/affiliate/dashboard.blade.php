@php
    use App\Enums\AffiliateBankVerificationStatus;
    use App\Enums\AffiliateCommissionStatus;
    use App\Enums\AffiliateWithdrawalStatus;
    use App\Support\AffiliateBankOptions;
    use App\Support\IndonesianNumber;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $firstName = str(auth()->user()->name)->before(' ');

    $shareText = 'Booking muthowif di BaytGo pakai kode affiliate saya: '.$affiliate->code.' — '.$shareUrl;

    // Sparkline 30 hari
    $sparkW = 100;
    $sparkH = 28;
    $sparkPoints = function (array $series) use ($sparkW, $sparkH): string {
        $count = count($series);
        $max = max(1, ...$series);
        if ($count <= 1) {
            return '0,'.$sparkH.' '.$sparkW.','.$sparkH;
        }
        $pts = [];
        foreach ($series as $i => $v) {
            $x = ($i / ($count - 1)) * $sparkW;
            $y = $sparkH - (((float) $v) / $max) * ($sparkH - 2) - 1;
            $pts[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $pts);
    };
    $amountPoly = $sparkPoints($chart['amounts']);
    $countPoly = $sparkPoints($chart['counts']);

    $commissionBadge = fn (AffiliateCommissionStatus $s) => match ($s) {
        AffiliateCommissionStatus::Available => 'bg-emerald-50 text-emerald-800 ring-emerald-200/70',
        AffiliateCommissionStatus::Pending => 'bg-amber-50 text-amber-800 ring-amber-200/70',
        AffiliateCommissionStatus::Void => 'bg-red-50 text-red-700 ring-red-200/70',
    };
    $withdrawalBadge = fn (AffiliateWithdrawalStatus $s) => match ($s) {
        AffiliateWithdrawalStatus::Paid => 'bg-emerald-50 text-emerald-800 ring-emerald-200/70',
        AffiliateWithdrawalStatus::Approved => 'bg-blue-50 text-blue-800 ring-blue-200/70',
        AffiliateWithdrawalStatus::Requested => 'bg-amber-50 text-amber-800 ring-amber-200/70',
        AffiliateWithdrawalStatus::Rejected, AffiliateWithdrawalStatus::Failed => 'bg-red-50 text-red-700 ring-red-200/70',
    };

    $primaryBank = $bankAccounts->firstWhere('is_primary', true) ?? $bankAccounts->first();
@endphp
<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            {{-- ── Hero ─────────────────────────────────────────────────── --}}
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-900 p-6 text-white shadow-xl sm:p-8"
                x-data="{
                    copied: null,
                    copy(text, which) {
                        navigator.clipboard.writeText(text);
                        this.copied = which;
                        setTimeout(() => { if (this.copied === which) this.copied = null; }, 1600);
                    },
                    share() {
                        if (navigator.share) {
                            navigator.share({ text: @js($shareText) }).catch(() => {});
                        } else {
                            this.copy(@js($shareText), 'share');
                        }
                    },
                }"
            >
                <div class="pointer-events-none absolute -right-10 -top-16 h-64 w-64 rounded-full bg-emerald-400/10 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 right-32 h-64 w-64 rounded-full bg-amber-300/10 blur-3xl" aria-hidden="true"></div>

                <div class="relative grid gap-6 lg:grid-cols-12 lg:items-center">
                    <div class="lg:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">Affiliate Dashboard</p>
                        <h1 class="mt-1.5 text-2xl font-bold sm:text-3xl">Halo, {{ $firstName }}!</h1>
                        <p class="mt-2 text-sm leading-relaxed text-emerald-100/80">
                            Ajak lebih banyak jamaah dan dapatkan komisi dari setiap booking yang berhasil.
                        </p>
                    </div>

                    <div class="lg:col-span-4">
                        <p class="text-xs font-semibold text-emerald-200">Kode Affiliate Anda</p>
                        <button
                            type="button"
                            @click="copy(@js($affiliate->code), 'code')"
                            class="mt-2 inline-flex w-full items-center justify-between gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-left transition hover:bg-white/15"
                        >
                            <span class="font-mono text-lg font-bold tracking-widest">{{ $affiliate->code }}</span>
                            <span class="shrink-0 text-emerald-200">
                                <svg x-show="copied !== 'code'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                <svg x-show="copied === 'code'" x-cloak class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                        </button>
                        <p class="mt-1 h-4 text-[11px] text-emerald-300" x-show="copied === 'code'" x-cloak>Kode tersalin!</p>
                    </div>

                    <div class="lg:col-span-4">
                        <p class="text-xs font-semibold text-emerald-200">Referral Link Anda</p>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="text" readonly value="{{ $shareUrl }}" class="min-w-0 flex-1 truncate rounded-xl border-0 bg-white/10 px-3 py-2.5 text-xs text-emerald-50 ring-1 ring-white/15 focus:ring-white/30">
                            <button
                                type="button"
                                @click="copy(@js($shareUrl), 'link')"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-emerald-100 ring-1 ring-white/15 transition hover:bg-white/20"
                                title="Salin link"
                            >
                                <svg x-show="copied !== 'link'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                <svg x-show="copied === 'link'" x-cloak class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </button>
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-2">
                            <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                WhatsApp
                            </a>
                            <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareText) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                Telegram
                            </a>
                            <button type="button" @click="share()" class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" /></svg>
                                <span x-text="copied === 'share' ? 'Tersalin!' : 'Bagikan'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Kartu statistik ──────────────────────────────────────── --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Saldo Tersedia</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" /></svg>
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">Rp {{ $fmt($stats['available_balance']) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">Siap ditarik kapan saja</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Pending Commission</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">Rp {{ $fmt($stats['pending_commission']) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ $stats['pending_count'] }} booking menunggu selesai</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Booking Berhasil</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $stats['success_booking'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">dari {{ $stats['total_booking'] }} booking beratribusi</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Withdraw</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">Rp {{ $fmt($stats['total_withdraw']) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">Total penarikan dibayar</p>
                </div>
            </div>

            {{-- ── Statistik & tips ─────────────────────────────────────── --}}
            <div class="grid gap-4 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                    <h2 class="font-semibold text-slate-900">Statistik 30 Hari Terakhir</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-xs font-semibold text-slate-500">Komisi Diperoleh</p>
                            </div>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">Rp {{ $fmt($chart['total_amount']) }}</p>
                            <div class="mt-3 h-14">
                                <svg class="h-full w-full" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="affAmountFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <polygon points="0,28 {{ $amountPoly }} 100,28" fill="url(#affAmountFill)" />
                                    <polyline points="{{ $amountPoly }}" fill="none" stroke="#10b981" stroke-width="1" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                </svg>
                            </div>
                            <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                                <span>{{ $chart['labels'][0] ?? '' }}</span>
                                <span>{{ $chart['labels'][count($chart['labels']) - 1] ?? '' }}</span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-xs font-semibold text-slate-500">Booking Beratribusi</p>
                            </div>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">{{ $chart['total_count'] }}</p>
                            <div class="mt-3 h-14">
                                <svg class="h-full w-full" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="affCountFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.22" />
                                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <polygon points="0,28 {{ $countPoly }} 100,28" fill="url(#affCountFill)" />
                                    <polyline points="{{ $countPoly }}" fill="none" stroke="#3b82f6" stroke-width="1" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                </svg>
                            </div>
                            <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                                <span>{{ $chart['labels'][0] ?? '' }}</span>
                                <span>{{ $chart['labels'][count($chart['labels']) - 1] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Tips Meningkatkan Komisi</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-2.5">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            Bagikan kode Anda ke grup WhatsApp jamaah.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            Promosikan di media sosial Anda.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            Ingatkan jamaah memasukkan kode Anda saat mengisi form booking.
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            Komisi cair otomatis ke saldo setelah booking selesai.
                        </li>
                    </ul>
                    <p class="mt-4 rounded-xl bg-slate-50 px-3 py-2.5 text-[11px] leading-relaxed text-slate-500 ring-1 ring-slate-100">
                        Status akun: <span class="font-semibold text-slate-700">{{ $affiliate->status->label() }}</span>. Komisi mengikuti pengaturan yang berlaku saat booking dibuat.
                    </p>
                </section>
            </div>

            {{-- ── Rekening payout & request withdraw ───────────────────── --}}
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ addBank: {{ $bankAccounts->isEmpty() ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-900">Rekening Payout</h2>
                        <button type="button" @click="addBank = !addBank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span x-text="addBank ? 'Tutup form' : 'Tambah rekening'"></span>
                        </button>
                    </div>

                    @if ($primaryBank !== null)
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" /></svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ $primaryBank->bank_name }}</p>
                                        <p class="font-mono text-sm tracking-widest text-slate-600">**** **** {{ substr((string) $primaryBank->account_number, -4) }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Atas nama <span class="font-semibold text-slate-700">{{ $primaryBank->account_holder }}</span></p>
                                    </div>
                                </div>
                                @if ($primaryBank->verification_status === AffiliateBankVerificationStatus::Verified || $primaryBank->verification_status === AffiliateBankVerificationStatus::Pending)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-800 ring-1 ring-emerald-200/70">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        Siap dipakai
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-800 ring-1 ring-red-200/70">{{ $primaryBank->verification_status->label() }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($bankAccounts->count() > ($primaryBank !== null ? 1 : 0))
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($bankAccounts as $bank)
                                @continue($primaryBank !== null && $bank->is($primaryBank))
                                <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-800">{{ $bank->bank_name }} · **** {{ substr((string) $bank->account_number, -4) }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $bank->account_holder }}
                                            @if ($bank->is_primary)
                                                · <span class="text-emerald-700">Primary</span>
                                            @endif
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('affiliate.bank-accounts.destroy', $bank) }}" onsubmit="return confirm('Hapus rekening ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600 hover:text-red-700">Hapus</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($bankAccounts->isEmpty())
                        <p class="mt-4 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-700 ring-1 ring-slate-200/70">Tambahkan rekening untuk menarik saldo komisi.</p>
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
                        <x-input-error :messages="$errors->get('bank_code')" />
                        <x-input-error :messages="$errors->get('account_holder')" />
                        <x-input-error :messages="$errors->get('account_number')" />
                        <x-submit-button class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                            Tambah rekening
                        </x-submit-button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ amount: @js((string) old('amount', '')) }">
                    <h2 class="font-semibold text-slate-900">Request Withdraw</h2>
                    <div class="mt-4 flex flex-wrap items-end justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Saldo tersedia</p>
                            <p class="text-xl font-bold tabular-nums text-slate-900">Rp {{ $fmt($stats['available_balance']) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Minimal withdraw</p>
                            <p class="text-sm font-semibold tabular-nums text-slate-700">Rp {{ $fmt($stats['min_withdraw']) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('affiliate.withdrawals.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <div class="flex flex-wrap gap-2">
                            @foreach ([100000, 250000, 500000] as $quick)
                                <button type="button" @click="amount = '{{ $quick }}'" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                                    Rp {{ $fmt($quick) }}
                                </button>
                            @endforeach
                            <button type="button" @click="amount = '{{ (int) $stats['available_balance'] }}'" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                Semua Saldo
                            </button>
                        </div>
                        <div>
                            <x-input-label for="amount" value="Nominal" />
                            <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" x-model="amount" min="1" step="1" required placeholder="Masukkan nominal" />
                            <x-input-error :messages="$errors->get('amount')" />
                        </div>
                        <div>
                            <x-input-label for="bank_account_id" value="Rekening tujuan" />
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
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                            Ajukan Withdraw
                        </button>
                    </form>
                </section>
            </div>

            {{-- ── Riwayat komisi ───────────────────────────────────────── --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Riwayat Komisi</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Booking</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($commissions as $commission)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900">{{ $commission->booking?->booking_code ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $commission->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">Rp {{ $fmt((float) $commission->commission_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $commissionBadge($commission->status) }}">{{ $commission->status->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada komisi. Bagikan kode Anda untuk mulai mendapatkan komisi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($commissions->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $commissions->links() }}</div>
                @endif
            </section>

            {{-- ── Riwayat withdraw ─────────────────────────────────────── --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Riwayat Withdraw</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3">Rekening</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($withdrawals as $withdrawal)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $withdrawal->requested_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ $fmt((float) $withdrawal->amount) }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $withdrawal->beneficiary_bank }} · **** {{ substr((string) $withdrawal->beneficiary_account, -4) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $withdrawalBadge($withdrawal->status) }}">{{ $withdrawal->status->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada withdraw.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($withdrawals->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $withdrawals->links() }}</div>
                @endif
            </section>

            {{-- ── Ledger wallet ────────────────────────────────────────── --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Ledger Wallet</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ledger as $entry)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $entry->occurred_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $entry->type->label() }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ (float) $entry->amount >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                        {{ (float) $entry->amount >= 0 ? '+' : '−' }}Rp {{ $fmt(abs((float) $entry->amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-900">Rp {{ $fmt((float) $entry->balance_after) }}</td>
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
            </section>
        </x-page-container>
    </div>
</x-app-layout>
