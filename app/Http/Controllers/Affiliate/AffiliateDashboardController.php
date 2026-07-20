<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWalletTransaction;
use App\Models\AffiliateWithdrawal;
use App\Services\AffiliateRegistrationService;
use App\Support\AffiliateSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        $affiliate = Affiliate::query()->where('user_id', $user->id)->first();
        if ($affiliate === null) {
            return view('affiliate.register');
        }

        $this->authorize('view', $affiliate);

        $volume = $affiliate->attributedVolume();
        $level = AffiliateSettings::resolveLevel($volume);
        $currentMin = (float) $level['min'];
        $nextMin = $level['next_min'];
        $levelProgress = 100.0;
        $remainingToNext = null;

        if ($nextMin !== null && (float) $nextMin > $currentMin) {
            $span = (float) $nextMin - $currentMin;
            $levelProgress = min(100.0, max(0.0, (($volume - $currentMin) / $span) * 100));
            $remainingToNext = max(0.0, (float) $nextMin - $volume);
        }

        $mom = $this->monthOverMonth($affiliate);

        $stats = [
            'available_balance' => (float) $affiliate->available_balance,
            'pending_commission' => (float) AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', AffiliateCommissionStatus::Pending)
                ->sum('commission_amount'),
            'total_commission' => (float) AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->whereIn('status', [
                    AffiliateCommissionStatus::Pending->value,
                    AffiliateCommissionStatus::Available->value,
                ])
                ->sum('commission_amount'),
            'total_booking' => (int) AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->count(),
            'success_booking' => (int) AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', AffiliateCommissionStatus::Available)
                ->count(),
            'pending_count' => (int) AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', AffiliateCommissionStatus::Pending)
                ->count(),
            'total_withdraw' => (float) AffiliateWithdrawal::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', AffiliateWithdrawalStatus::Paid)
                ->sum('amount'),
            'total_clicks' => (int) $affiliate->clicks()->count(),
            'volume' => $volume,
            'level' => $level['level'],
            'level_label' => $level['label'],
            'rate' => $level['rate'],
            'min' => $currentMin,
            'next_min' => $nextMin,
            'level_progress' => $levelProgress,
            'remaining_to_next' => $remainingToNext,
            'tiers' => AffiliateSettings::getTiers(),
            'min_withdraw' => AffiliateSettings::getMinWithdraw(),
            'mom' => $mom,
        ];

        $chart = $this->dailySeries($affiliate, 30);

        $commissions = AffiliateCommission::query()
            ->with('booking')
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('created_at')
            ->paginate(8, ['*'], 'commission_page');

        $withdrawals = AffiliateWithdrawal::query()
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('requested_at')
            ->paginate(10, ['*'], 'withdraw_page');

        $ledger = AffiliateWalletTransaction::query()
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('occurred_at')
            ->paginate(15, ['*'], 'ledger_page');

        $bankAccounts = $affiliate->bankAccounts()->orderByDesc('is_primary')->orderByDesc('created_at')->get();

        return view('affiliate.dashboard', [
            'affiliate' => $affiliate,
            'stats' => $stats,
            'chart' => $chart,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'ledger' => $ledger,
            'bankAccounts' => $bankAccounts,
            'shareUrl' => url('/r/'.$affiliate->code),
            'shareUrlDisplay' => parse_url(url('/r/'.$affiliate->code), PHP_URL_HOST).'/r/'.$affiliate->code,
        ]);
    }

    /**
     * @return array{
     *     commission_delta: float,
     *     clicks_delta_pct: float,
     *     balance_delta: float,
     *     pending_delta: float,
     *     booking_delta: int,
     *     withdraw_delta: float,
     *     commission_this_month: float
     * }
     */
    private function monthOverMonth(Affiliate $affiliate): array
    {
        $thisStart = now()->startOfMonth();
        $lastStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastEnd = (clone $thisStart)->subSecond();

        $commissionThis = (float) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->whereIn('status', [AffiliateCommissionStatus::Pending->value, AffiliateCommissionStatus::Available->value])
            ->where('created_at', '>=', $thisStart)
            ->sum('commission_amount');

        $commissionLast = (float) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->whereIn('status', [AffiliateCommissionStatus::Pending->value, AffiliateCommissionStatus::Available->value])
            ->whereBetween('created_at', [$lastStart, $lastEnd])
            ->sum('commission_amount');

        $clicksThis = (int) AffiliateClick::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('created_at', '>=', $thisStart)
            ->count();

        $clicksLast = (int) AffiliateClick::query()
            ->where('affiliate_id', $affiliate->id)
            ->whereBetween('created_at', [$lastStart, $lastEnd])
            ->count();

        $bookingThis = (int) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateCommissionStatus::Available)
            ->where('available_at', '>=', $thisStart)
            ->count();

        $bookingLast = (int) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateCommissionStatus::Available)
            ->whereBetween('available_at', [$lastStart, $lastEnd])
            ->count();

        $withdrawThis = (float) AffiliateWithdrawal::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateWithdrawalStatus::Paid)
            ->where('paid_at', '>=', $thisStart)
            ->sum('amount');

        $withdrawLast = (float) AffiliateWithdrawal::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateWithdrawalStatus::Paid)
            ->whereBetween('paid_at', [$lastStart, $lastEnd])
            ->sum('amount');

        $pendingThis = (float) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateCommissionStatus::Pending)
            ->where('pending_at', '>=', $thisStart)
            ->sum('commission_amount');

        $pendingLast = (float) AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', AffiliateCommissionStatus::Pending)
            ->whereBetween('pending_at', [$lastStart, $lastEnd])
            ->sum('commission_amount');

        $clicksDeltaPct = $clicksLast > 0
            ? (($clicksThis - $clicksLast) / $clicksLast) * 100
            : ($clicksThis > 0 ? 100.0 : 0.0);

        return [
            'commission_delta' => round($commissionThis - $commissionLast, 2),
            'clicks_delta_pct' => round($clicksDeltaPct, 1),
            'balance_delta' => round($commissionThis - $commissionLast, 2),
            'pending_delta' => round($pendingThis - $pendingLast, 2),
            'booking_delta' => $bookingThis - $bookingLast,
            'withdraw_delta' => round($withdrawThis - $withdrawLast, 2),
            'commission_this_month' => $commissionThis,
        ];
    }

    /**
     * @return array{labels: list<string>, amounts: list<int>, counts: list<int>, total_amount: int, total_count: int, max_amount: int}
     */
    private function dailySeries(Affiliate $affiliate, int $daysBack): array
    {
        $start = now()->subDays($daysBack - 1)->startOfDay();

        $rows = AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->whereIn('status', [
                AffiliateCommissionStatus::Pending->value,
                AffiliateCommissionStatus::Available->value,
            ])
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'commission_amount'])
            ->groupBy(fn ($c) => $c->created_at->toDateString());

        $labels = [];
        $amounts = [];
        $counts = [];
        $totalAmount = 0;
        $totalCount = 0;

        for ($d = $start->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->translatedFormat('d M');
            $group = $rows->get($key);
            $amount = (int) round((float) ($group?->sum(fn ($c) => (float) $c->commission_amount) ?? 0));
            $count = (int) ($group?->count() ?? 0);
            $amounts[] = $amount;
            $counts[] = $count;
            $totalAmount += $amount;
            $totalCount += $count;
        }

        return [
            'labels' => $labels,
            'amounts' => $amounts,
            'counts' => $counts,
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'max_amount' => max(1, ...$amounts),
        ];
    }

    public function register(Request $request, AffiliateRegistrationService $registration): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('register', Affiliate::class);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
        ]);

        $registration->register($user, $validated['code'] ?? null);

        return redirect()
            ->route('affiliate.index')
            ->with('status', 'Akun affiliate berhasil diaktifkan.');
    }
}
