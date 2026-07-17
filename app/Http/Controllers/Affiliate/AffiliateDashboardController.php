<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
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
            'rate' => AffiliateSettings::getRate(),
            'min_withdraw' => AffiliateSettings::getMinWithdraw(),
        ];

        $chart = $this->dailySeries($affiliate, 30);

        $commissions = AffiliateCommission::query()
            ->with('booking')
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'commission_page');

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
            'shareUrl' => url('/?ref='.$affiliate->code),
        ]);
    }

    /**
     * Seri harian N hari terakhir: nominal komisi & jumlah booking beratribusi.
     *
     * @return array{labels: list<string>, amounts: list<int>, counts: list<int>, total_amount: int, total_count: int}
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
