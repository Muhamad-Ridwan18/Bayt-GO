<?php

namespace App\Http\Controllers\Affiliate;

use App\Events\AffiliateWithdrawalRequested;
use App\Http\Controllers\Controller;
use App\Models\AffiliateBankAccount;
use App\Services\AffiliateWalletService;
use App\Support\AffiliateSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AffiliateWithdrawController extends Controller
{
    public function store(Request $request, AffiliateWalletService $wallet): RedirectResponse
    {
        $affiliate = $request->user()->affiliate;
        abort_unless($affiliate !== null, 403);
        $this->authorize('manage', $affiliate);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $bank = AffiliateBankAccount::query()
            ->whereKey($validated['bank_account_id'])
            ->where('affiliate_id', $affiliate->id)
            ->first();

        if ($bank === null) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['Pilih rekening yang valid.'],
            ]);
        }

        $withdrawal = $wallet->requestWithdrawal(
            $affiliate,
            $bank,
            (float) $validated['amount'],
            $validated['notes'] ?? null,
        );

        event(new AffiliateWithdrawalRequested($withdrawal));

        return back()->with('status', 'Permintaan withdraw dikirim. Minimal Rp '.number_format(AffiliateSettings::getMinWithdraw(), 0, ',', '.').'.');
    }
}
