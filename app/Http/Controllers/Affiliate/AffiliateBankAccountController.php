<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\AffiliateBankVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AffiliateBankAccount;
use App\Support\AffiliateBankOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AffiliateBankAccountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $affiliate = $request->user()->affiliate;
        abort_unless($affiliate !== null, 403);
        $this->authorize('manage', $affiliate);

        $validated = $request->validate([
            'bank_code' => ['required', 'string', Rule::in(array_keys(AffiliateBankOptions::all()))],
            'account_holder' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            $affiliate->bankAccounts()->update(['is_primary' => false]);
        }

        $affiliate->bankAccounts()->create([
            'bank_code' => $validated['bank_code'],
            'bank_name' => AffiliateBankOptions::label($validated['bank_code']),
            'account_holder' => trim($validated['account_holder']),
            'account_number' => trim($validated['account_number']),
            'is_primary' => $request->boolean('is_primary') || $affiliate->bankAccounts()->count() === 0,
            'verification_status' => AffiliateBankVerificationStatus::Verified,
            'verified_at' => now(),
        ]);

        return back()->with('status', 'Rekening berhasil ditambahkan.');
    }

    public function destroy(Request $request, AffiliateBankAccount $bankAccount): RedirectResponse
    {
        $affiliate = $request->user()->affiliate;
        abort_unless($affiliate !== null && (string) $bankAccount->affiliate_id === (string) $affiliate->id, 403);
        $this->authorize('manage', $affiliate);

        $bankAccount->delete();

        return back()->with('status', 'Rekening dihapus.');
    }
}
