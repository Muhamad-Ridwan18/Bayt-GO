<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Events\WithdrawalRequested;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use App\Services\MuthowifWalletLedger;
use App\Support\AffiliateBankOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        if (!$profile instanceof MuthowifProfile) {
            return response()->json(['message' => 'Profile muthowif tidak ditemukan.'], 403);
        }

        $withdrawals = MuthowifWithdrawal::query()
            ->where('muthowif_profile_id', $profile->id)
            ->orderByDesc('requested_at')
            ->take(20)
            ->get();

        $ledgerEntries = MuthowifWalletLedger::entriesForProfile($profile)
            ->take(30)
            ->map(function ($entry) {
                return [
                    'kind' => $entry['kind'],
                    'signed_amount' => $entry['signed_amount'],
                    'at' => $entry['at']->format('Y-m-d H:i:s'),
                    'tie' => $entry['tie'],
                    'booking_code' => $entry['booking'] ? $entry['booking']->booking_code : null,
                    'withdrawal_id' => $entry['withdrawal'] ? $entry['withdrawal']->id : null,
                    'withdrawal_bank' => $entry['withdrawal'] ? $entry['withdrawal']->beneficiary_bank : null,
                    'withdrawal_account' => $entry['withdrawal'] ? $entry['withdrawal']->beneficiary_account : null,
                ];
            });

        return response()->json([
            'balance' => (float) ($profile->wallet_balance ?? 0),
            'ledger' => $ledgerEntries,
            'withdrawals' => $withdrawals,
            'bank_options' => AffiliateBankOptions::all(),
            'banks' => AffiliateBankOptions::optionsWithLogos(),
        ]);
    }

    public function storeWithdrawal(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        if (!$profile instanceof MuthowifProfile) {
            return response()->json(['message' => 'Profile muthowif tidak ditemukan.'], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10000'],
            'beneficiary_name' => ['required', 'string', 'max:100'],
            'beneficiary_bank' => ['required', 'string', 'max:64', Rule::in(array_keys(AffiliateBankOptions::all()))],
            'beneficiary_account' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        
        if ($amount > (float) $profile->wallet_balance) {
            return response()->json(['message' => 'Saldo tidak mencukupi.'], 422);
        }

        $withdrawal = MuthowifWithdrawal::query()->create([
            'muthowif_profile_id' => $profile->id,
            'amount' => $amount,
            'beneficiary_name' => $validated['beneficiary_name'],
            'beneficiary_bank' => $validated['beneficiary_bank'],
            'beneficiary_account' => $validated['beneficiary_account'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending_approval',
            'requested_at' => now(),
        ]);

        WithdrawalRequested::dispatch($withdrawal);

        return response()->json([
            'message' => 'Permintaan withdraw berhasil diajukan.',
            'withdrawal' => $withdrawal,
        ], 201);
    }
}
