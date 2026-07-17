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
            'total_withdraw' => (float) AffiliateWithdrawal::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', AffiliateWithdrawalStatus::Paid)
                ->sum('amount'),
            'rate' => AffiliateSettings::getRate(),
            'min_withdraw' => AffiliateSettings::getMinWithdraw(),
        ];

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
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'ledger' => $ledger,
            'bankAccounts' => $bankAccounts,
            'shareUrl' => url('/?ref='.$affiliate->code),
        ]);
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
