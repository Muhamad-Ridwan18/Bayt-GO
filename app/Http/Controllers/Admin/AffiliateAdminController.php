<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AffiliateBankVerificationStatus;
use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateBankAccount;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawal;
use App\Services\AffiliateWalletService;
use App\Support\AffiliateSettings;
use App\Support\PlatformFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliateAdminController extends Controller
{
    public function index(): View
    {
        $affiliates = Affiliate::query()
            ->with('user')
            ->withCount([
                'commissions',
                'commissions as available_commissions_count' => fn ($q) => $q->where('status', AffiliateCommissionStatus::Available),
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total_affiliate' => Affiliate::query()->count(),
            'active_affiliate' => Affiliate::query()->where('status', AffiliateStatus::Active)->count(),
            'total_commission' => (float) AffiliateCommission::query()
                ->whereIn('status', [AffiliateCommissionStatus::Pending->value, AffiliateCommissionStatus::Available->value])
                ->sum('commission_amount'),
            'pending_commission' => (float) AffiliateCommission::query()
                ->where('status', AffiliateCommissionStatus::Pending)
                ->sum('commission_amount'),
            'pending_withdraw' => (int) AffiliateWithdrawal::query()
                ->whereIn('status', [AffiliateWithdrawalStatus::Requested->value, AffiliateWithdrawalStatus::Approved->value])
                ->count(),
            'tiers' => AffiliateSettings::getTiers(),
            'min_withdraw' => AffiliateSettings::getMinWithdraw(),
        ];

        return view('admin.affiliates.index', compact('affiliates', 'stats'));
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->load(['user', 'bankAccounts', 'commissions.booking', 'withdrawals']);

        return view('admin.affiliates.show', compact('affiliate'));
    }

    public function toggleStatus(Affiliate $affiliate): RedirectResponse
    {
        if ($affiliate->status === AffiliateStatus::Active) {
            $affiliate->update([
                'status' => AffiliateStatus::Inactive,
                'deactivated_at' => now(),
            ]);
            $msg = 'Affiliate dinonaktifkan.';
        } else {
            $affiliate->update([
                'status' => AffiliateStatus::Active,
                'activated_at' => $affiliate->activated_at ?? now(),
                'deactivated_at' => null,
            ]);
            $msg = 'Affiliate diaktifkan.';
        }

        return back()->with('status', $msg);
    }

    public function verifyBank(Request $request, AffiliateBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update([
            'verification_status' => AffiliateBankVerificationStatus::Verified,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Rekening diverifikasi.');
    }

    public function rejectBank(Request $request, AffiliateBankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $bankAccount->update([
            'verification_status' => AffiliateBankVerificationStatus::Rejected,
            'verified_at' => null,
            'verified_by' => $request->user()->id,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('status', 'Rekening ditolak.');
    }

    public function settingsEdit(): View
    {
        return view('admin.affiliates.settings', [
            'tiers' => AffiliateSettings::getTiers(),
            'minWithdraw' => AffiliateSettings::getMinWithdraw(),
            'platformFeeTotalRate' => PlatformFee::getTotalRate(),
        ]);
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tiers' => ['required', 'array', 'min:3', 'max:3'],
            'tiers.*.min' => ['required', 'numeric', 'min:0'],
            'tiers.*.rate_percent' => ['required', 'numeric', 'min:0.01', 'max:50'],
            'min_withdraw' => ['required', 'numeric', 'min:1000'],
        ]);

        $tiers = [];
        $platformMax = PlatformFee::getTotalRate();
        $prevMin = -1.0;

        foreach (array_values($validated['tiers']) as $i => $row) {
            $min = $i === 0 ? 0.0 : round((float) $row['min'], 2);
            $rate = round(((float) $row['rate_percent']) / 100, 6);

            if ($rate > $platformMax) {
                throw ValidationException::withMessages([
                    "tiers.$i.rate_percent" => ['Rate affiliate tidak boleh melebihi total platform fee.'],
                ]);
            }

            if ($i > 0 && $min <= $prevMin) {
                throw ValidationException::withMessages([
                    "tiers.$i.min" => ['Omzet minimal level harus lebih besar dari level sebelumnya.'],
                ]);
            }

            $tiers[] = ['min' => $min, 'rate' => $rate];
            $prevMin = $min;
        }

        AffiliateSettings::putTiers($tiers);
        AffiliateSettings::putMinWithdraw((float) $validated['min_withdraw']);

        return back()->with('status', 'Pengaturan affiliate disimpan. Berlaku untuk booking baru.');
    }

    public function withdrawalsIndex(): RedirectResponse
    {
        return redirect()->route('admin.withdrawals.index', ['tab' => 'affiliate']);
    }

    public function approveWithdrawal(AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet, Request $request): RedirectResponse
    {
        $wallet->approve($withdrawal, $request->user());

        return redirect()
            ->route('admin.withdrawals.index', ['tab' => 'affiliate'])
            ->with('status', 'Withdraw disetujui.');
    }

    public function rejectWithdrawal(AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet, Request $request): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $wallet->reject($withdrawal, $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('admin.withdrawals.index', ['tab' => 'affiliate'])
            ->with('status', 'Withdraw ditolak, saldo dikembalikan.');
    }

    public function markWithdrawalPaid(AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $path = $request->file('transfer_proof')->store('affiliate-withdrawals/proofs', 'public');
        $wallet->markPaid($withdrawal, $request->user(), $path);

        return redirect()
            ->route('admin.withdrawals.index', ['tab' => 'affiliate'])
            ->with('status', 'Withdraw ditandai dibayar.');
    }

    public function markWithdrawalFailed(AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet, Request $request): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $wallet->markFailed($withdrawal, $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('admin.withdrawals.index', ['tab' => 'affiliate'])
            ->with('status', 'Withdraw ditandai gagal, saldo dikembalikan.');
    }
}
