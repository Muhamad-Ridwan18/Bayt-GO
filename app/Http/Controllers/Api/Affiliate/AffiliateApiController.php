<?php

namespace App\Http\Controllers\Api\Affiliate;

use App\Enums\AffiliateBankVerificationStatus;
use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Events\AffiliateWithdrawalRequested;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateBankAccount;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWalletTransaction;
use App\Models\AffiliateWithdrawal;
use App\Services\AffiliateRegistrationService;
use App\Services\AffiliateWalletService;
use App\Support\AffiliateBankOptions;
use App\Support\AffiliateSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AffiliateApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $affiliate = Affiliate::query()->where('user_id', $user->id)->first();
        if ($affiliate === null) {
            return response()->json([
                'registered' => false,
                'default_rate' => AffiliateSettings::getRate(),
                'min_withdraw' => AffiliateSettings::getMinWithdraw(),
            ]);
        }

        return response()->json([
            'registered' => true,
            'affiliate' => $this->affiliatePayload($affiliate),
        ]);
    }

    public function register(Request $request, AffiliateRegistrationService $registration): JsonResponse
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $existing = Affiliate::query()->where('user_id', $user->id)->first();
        if ($existing !== null) {
            return response()->json([
                'message' => 'Sudah terdaftar sebagai affiliate',
                'affiliate' => $this->affiliatePayload($existing),
            ]);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
        ]);

        $affiliate = $registration->register($user, $validated['code'] ?? null);

        return response()->json([
            'message' => 'Affiliate diaktifkan',
            'affiliate' => $this->affiliatePayload($affiliate),
        ], 201);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $affiliate = $this->requireAffiliate($request);

        return response()->json([
            'affiliate' => $this->affiliatePayload($affiliate),
            'stats' => [
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
                'total_booking' => (int) AffiliateCommission::query()->where('affiliate_id', $affiliate->id)->count(),
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
            ],
            'share_url' => url('/?ref='.$affiliate->code),
            'commissions' => AffiliateCommission::query()
                ->with('booking:id,booking_code')
                ->where('affiliate_id', $affiliate->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'withdrawals' => AffiliateWithdrawal::query()
                ->where('affiliate_id', $affiliate->id)
                ->orderByDesc('requested_at')
                ->limit(20)
                ->get(),
            'ledger' => AffiliateWalletTransaction::query()
                ->where('affiliate_id', $affiliate->id)
                ->orderByDesc('occurred_at')
                ->limit(30)
                ->get(),
            'bank_accounts' => $affiliate->bankAccounts()->orderByDesc('is_primary')->get(),
            'bank_options' => AffiliateBankOptions::all(),
        ]);
    }

    public function storeBankAccount(Request $request): JsonResponse
    {
        $affiliate = $this->requireAffiliate($request);

        $validated = $request->validate([
            'bank_code' => ['required', 'string', Rule::in(array_keys(AffiliateBankOptions::all()))],
            'account_holder' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            $affiliate->bankAccounts()->update(['is_primary' => false]);
        }

        $bank = $affiliate->bankAccounts()->create([
            'bank_code' => $validated['bank_code'],
            'bank_name' => AffiliateBankOptions::label($validated['bank_code']),
            'account_holder' => trim($validated['account_holder']),
            'account_number' => trim($validated['account_number']),
            'is_primary' => $request->boolean('is_primary') || $affiliate->bankAccounts()->count() === 0,
            'verification_status' => AffiliateBankVerificationStatus::Pending,
        ]);

        return response()->json(['message' => 'Rekening ditambahkan', 'bank_account' => $bank], 201);
    }

    public function destroyBankAccount(Request $request, string $id): JsonResponse
    {
        $affiliate = $this->requireAffiliate($request);
        $bank = AffiliateBankAccount::query()
            ->whereKey($id)
            ->where('affiliate_id', $affiliate->id)
            ->firstOrFail();
        $bank->delete();

        return response()->json(['message' => 'Rekening dihapus']);
    }

    public function storeWithdrawal(Request $request, AffiliateWalletService $wallet): JsonResponse
    {
        $affiliate = $this->requireAffiliate($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $bank = AffiliateBankAccount::query()
            ->whereKey($validated['bank_account_id'])
            ->where('affiliate_id', $affiliate->id)
            ->where('verification_status', AffiliateBankVerificationStatus::Verified)
            ->first();

        if ($bank === null) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['Pilih rekening yang sudah diverifikasi.'],
            ]);
        }

        $withdrawal = $wallet->requestWithdrawal(
            $affiliate,
            $bank,
            (float) $validated['amount'],
            $validated['notes'] ?? null,
        );

        event(new AffiliateWithdrawalRequested($withdrawal));

        return response()->json([
            'message' => 'Withdraw diajukan',
            'withdrawal' => $withdrawal,
            'available_balance' => (float) $affiliate->fresh()->available_balance,
        ], 201);
    }

    private function requireAffiliate(Request $request): Affiliate
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        $affiliate = Affiliate::query()->where('user_id', $user->id)->first();
        abort_if($affiliate === null, 404, 'Belum terdaftar sebagai affiliate');
        $this->authorize('view', $affiliate);

        return $affiliate;
    }

    /** @return array<string, mixed> */
    private function affiliatePayload(Affiliate $affiliate): array
    {
        return [
            'id' => $affiliate->id,
            'code' => $affiliate->code,
            'status' => $affiliate->status->value,
            'available_balance' => (float) $affiliate->available_balance,
            'activated_at' => $affiliate->activated_at?->toIso8601String(),
            'share_url' => url('/?ref='.$affiliate->code),
        ];
    }
}
