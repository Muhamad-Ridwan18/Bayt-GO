<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile instanceof MuthowifProfile, 403);

        $withdrawals = MuthowifWithdrawal::query()
            ->where('muthowif_profile_id', $profile->id)
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('muthowif.withdrawals.index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile instanceof MuthowifProfile, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'beneficiary_name' => ['required', 'string', 'max:100'],
            'beneficiary_bank' => ['required', 'string', 'max:64'],
            'beneficiary_account' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        if ($amount <= 0) {
            return back()->with('error', 'Nominal withdraw tidak valid.');
        }

        $withdrawal = MuthowifWithdrawal::query()->create([
            'muthowif_profile_id' => $profile->id,
            'amount' => $amount,
            'beneficiary_name' => (string) $validated['beneficiary_name'],
            'beneficiary_bank' => (string) $validated['beneficiary_bank'],
            'beneficiary_account' => (string) $validated['beneficiary_account'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending_approval',
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('muthowif.withdrawals.index')
            ->with('status', 'Permintaan withdraw berhasil dibuat. Tunggu persetujuan admin.');
    }
}

