<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use App\Services\XenditDisbursementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class WithdrawalsController extends Controller
{
    public function index(): View
    {
        $withdrawals = MuthowifWithdrawal::query()
            ->orderByDesc('requested_at')
            ->paginate(20);

        $pendingCount = (int) MuthowifWithdrawal::query()
            ->where('status', 'pending_approval')
            ->count();

        $pendingAmount = (float) MuthowifWithdrawal::query()
            ->where('status', 'pending_approval')
            ->sum('amount');

        return view('admin.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'pendingCount' => $pendingCount,
            'pendingAmount' => $pendingAmount,
        ]);
    }

    public function approve(Request $request, MuthowifWithdrawal $withdrawal, XenditDisbursementService $payout): RedirectResponse
    {
        abort_unless($withdrawal->status === 'pending_approval', 409);

        if (! $payout->isConfigured()) {
            return back()->with('error', 'Xendit disbursement belum terkonfigurasi di server.');
        }

        // Reserve dana terlebih dulu (debit wallet) supaya tidak bisa ada payout ganda
        // saat saldo berubah di sela request API Xendit disbursement.
        $reservedAmount = (float) $withdrawal->amount;

        try {
            DB::transaction(function () use ($withdrawal, $reservedAmount): void {
                $profile = MuthowifProfile::query()
                    ->whereKey($withdrawal->muthowif_profile_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $profile->wallet_balance < $reservedAmount) {
                    throw new RuntimeException('Saldo wallet muthowif tidak cukup untuk payout.');
                }

                $profile->wallet_balance = round((float) $profile->wallet_balance - $reservedAmount, 2);
                $profile->save();

                $withdrawal->update([
                    'status' => 'processing',
                    'approved_at' => now(),
                    'processing_at' => now(),
                    'failed_reason' => null,
                ]);
            });
        } catch (Throwable $e) {
            $withdrawal->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failed_reason' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.withdrawals.index')
                ->with('error', 'Saldo tidak cukup untuk approve payout.');
        }

        $payoutResult = null;
        try {
            $payoutResult = $payout->createDisbursement($withdrawal);
        } catch (Throwable $e) {
            // Refund saat request disbursement Xendit gagal dibuat.
            DB::transaction(function () use ($withdrawal, $reservedAmount, $e): void {
                $profile = MuthowifProfile::query()
                    ->whereKey($withdrawal->muthowif_profile_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $profile->wallet_balance = round((float) $profile->wallet_balance + $reservedAmount, 2);
                $profile->save();

                $withdrawal->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failed_reason' => $e->getMessage(),
                ]);
            });

            Log::error('Xendit disbursement create error', [
                'withdrawal_id' => (string) $withdrawal->getKey(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.withdrawals.index')
                ->with('error', 'Gagal membuat payout Xendit: '.$e->getMessage());
        }

        $referenceNo = $payoutResult['id'] ?? null;
        $initialStatus = $payoutResult['status'] ?? null;

        // Update status sesuai respons awal payout/disbursement.
        $newStatus = 'processing';
        $initialStatusLower = is_string($initialStatus) ? strtolower($initialStatus) : '';
        if (str_contains($initialStatusLower, 'success')
            || str_contains($initialStatusLower, 'completed')
            || str_contains($initialStatusLower, 'succeeded')
        ) {
            $newStatus = 'succeeded';
        } elseif (
            str_contains($initialStatusLower, 'failed')
            || str_contains($initialStatusLower, 'rejected')
        ) {
            $newStatus = 'failed';
        }

        $withdrawal->update([
            'midtrans_reference_no' => $referenceNo,
            'midtrans_initial_status' => $initialStatus,
            'status' => $newStatus,
            'processing_at' => $newStatus === 'processing' ? now() : $withdrawal->processing_at,
            'completed_at' => $newStatus === 'succeeded' ? now() : $withdrawal->completed_at,
            'failed_at' => $newStatus === 'failed' ? now() : $withdrawal->failed_at,
        ]);

        return redirect()
            ->route('admin.withdrawals.index')
            ->with('status', 'Withdraw disetujui dan payout Xendit diproses.');
    }
}

