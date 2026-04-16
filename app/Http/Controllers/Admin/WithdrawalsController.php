<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyMuthowifOfWithdrawalTransferProof;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Approve: debit saldo muthowif, status processing.
     * Admin menyelesaikan transfer ke rekening tujuan (mis. lewat bank), lalu menandai lewat markTransferred.
     */
    public function approve(Request $request, MuthowifWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->status === 'pending_approval', 409);

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

        return redirect()
            ->route('admin.withdrawals.index')
            ->with('status', 'Withdraw disetujui. Lakukan transfer ke rekening tujuan, lalu klik "Tandai transfer selesai".');
    }

    public function markTransferred(Request $request, MuthowifWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->status === 'processing', 409);

        $validated = $request->validate([
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $proofPath = $validated['transfer_proof']->store('withdrawals/proofs', 'public');

        $withdrawal->update([
            'status' => 'succeeded',
            'completed_at' => now(),
            'transfer_proof_path' => $proofPath,
        ]);

        NotifyMuthowifOfWithdrawalTransferProof::dispatchAfterResponse((string) $withdrawal->getKey());

        return redirect()
            ->route('admin.withdrawals.index')
            ->with('status', 'Transfer withdraw dicatat selesai. Bukti akan dikirim ke WhatsApp muthowif jika nomor valid.');
    }

    /**
     * Gagal transfer: kembalikan saldo ke wallet muthowif.
     */
    public function markTransferFailed(MuthowifWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->status === 'processing', 409);

        $amount = (float) $withdrawal->amount;

        DB::transaction(function () use ($withdrawal, $amount): void {
            $profile = MuthowifProfile::query()
                ->whereKey($withdrawal->muthowif_profile_id)
                ->lockForUpdate()
                ->firstOrFail();

            $profile->wallet_balance = round((float) $profile->wallet_balance + $amount, 2);
            $profile->save();

            $withdrawal->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failed_reason' => 'Transfer gagal atau dibatalkan (saldo dikembalikan).',
            ]);
        });

        return redirect()
            ->route('admin.withdrawals.index')
            ->with('status', 'Withdraw ditandai gagal; saldo muthowif dikembalikan.');
    }
}
