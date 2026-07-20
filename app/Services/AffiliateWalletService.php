<?php

namespace App\Services;

use App\Enums\AffiliateWalletTransactionType;
use App\Enums\AffiliateWithdrawalStatus;
use App\Models\Affiliate;
use App\Models\AffiliateBankAccount;
use App\Models\AffiliateWalletTransaction;
use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Support\AffiliateSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AffiliateWalletService
{
    public function credit(
        Affiliate $affiliate,
        float $amount,
        AffiliateWalletTransactionType $type,
        string $idempotencyKey,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $description = null,
        ?array $meta = null,
    ): AffiliateWalletTransaction {
        return DB::transaction(function () use ($affiliate, $amount, $type, $idempotencyKey, $sourceType, $sourceId, $description, $meta): AffiliateWalletTransaction {
            $existing = AffiliateWalletTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            /** @var Affiliate $locked */
            $locked = Affiliate::query()->whereKey($affiliate->getKey())->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);
            $balance = round((float) $locked->available_balance + $amount, 2);

            if ($balance < 0) {
                throw new RuntimeException('Saldo affiliate tidak mencukupi.');
            }

            $locked->available_balance = $balance;
            $locked->save();

            return AffiliateWalletTransaction::query()->create([
                'affiliate_id' => $locked->id,
                'amount' => $amount,
                'balance_after' => $balance,
                'type' => $type,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'meta' => $meta,
                'occurred_at' => now(),
            ]);
        });
    }

    public function requestWithdrawal(Affiliate $affiliate, AffiliateBankAccount $bankAccount, float $amount, ?string $notes = null): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($affiliate, $bankAccount, $amount, $notes): AffiliateWithdrawal {
            /** @var Affiliate $locked */
            $locked = Affiliate::query()->whereKey($affiliate->getKey())->lockForUpdate()->firstOrFail();

            /** @var AffiliateBankAccount $bank */
            $bank = AffiliateBankAccount::query()->whereKey($bankAccount->getKey())->lockForUpdate()->firstOrFail();

            if ((string) $bank->affiliate_id !== (string) $locked->id) {
                throw ValidationException::withMessages([
                    'bank_account_id' => ['Rekening tidak milik affiliate ini.'],
                ]);
            }

            $amount = round($amount, 2);
            $min = AffiliateSettings::getMinWithdraw();

            if ($amount < $min) {
                throw ValidationException::withMessages([
                    'amount' => ['Minimal withdraw Rp '.number_format($min, 0, ',', '.').'.'],
                ]);
            }

            if ($amount > (float) $locked->available_balance) {
                throw ValidationException::withMessages([
                    'amount' => ['Saldo affiliate tidak mencukupi.'],
                ]);
            }

            $withdrawal = AffiliateWithdrawal::query()->create([
                'affiliate_id' => $locked->id,
                'affiliate_bank_account_id' => $bank->id,
                'amount' => $amount,
                'beneficiary_name' => $bank->account_holder,
                'beneficiary_account' => $bank->account_number,
                'beneficiary_bank' => $bank->bank_code,
                'notes' => filled($notes) ? trim($notes) : null,
                'status' => AffiliateWithdrawalStatus::Requested,
                'requested_at' => now(),
            ]);

            $this->credit(
                $locked,
                -1 * $amount,
                AffiliateWalletTransactionType::WithdrawDebit,
                'withdraw-debit:'.$withdrawal->id,
                AffiliateWithdrawal::class,
                (string) $withdrawal->id,
                'Reservasi withdraw affiliate',
            );

            return $withdrawal->fresh();
        });
    }

    public function approve(AffiliateWithdrawal $withdrawal, User $admin): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin): AffiliateWithdrawal {
            /** @var AffiliateWithdrawal $locked */
            $locked = AffiliateWithdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== AffiliateWithdrawalStatus::Requested) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['Status withdraw tidak valid untuk disetujui.'],
                ]);
            }

            $locked->status = AffiliateWithdrawalStatus::Approved;
            $locked->approved_at = now();
            $locked->processed_by = $admin->id;
            $locked->save();

            app(AffiliateNotifier::class)->withdrawalApproved($locked);

            return $locked;
        });
    }

    public function reject(AffiliateWithdrawal $withdrawal, User $admin, ?string $reason = null): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason): AffiliateWithdrawal {
            /** @var AffiliateWithdrawal $locked */
            $locked = AffiliateWithdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [AffiliateWithdrawalStatus::Requested, AffiliateWithdrawalStatus::Approved], true)) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['Status withdraw tidak valid untuk ditolak.'],
                ]);
            }

            $affiliate = Affiliate::query()->whereKey($locked->affiliate_id)->lockForUpdate()->firstOrFail();

            $this->credit(
                $affiliate,
                (float) $locked->amount,
                AffiliateWalletTransactionType::WithdrawRelease,
                'withdraw-release:'.$locked->id,
                AffiliateWithdrawal::class,
                (string) $locked->id,
                'Withdraw ditolak, saldo dikembalikan',
                ['reason' => $reason],
            );

            $locked->status = AffiliateWithdrawalStatus::Rejected;
            $locked->rejected_at = now();
            $locked->processed_by = $admin->id;
            $locked->failed_reason = filled($reason) ? trim($reason) : null;
            $locked->save();

            return $locked;
        });
    }

    public function markPaid(AffiliateWithdrawal $withdrawal, User $admin, ?string $transferProofPath = null): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $transferProofPath): AffiliateWithdrawal {
            /** @var AffiliateWithdrawal $locked */
            $locked = AffiliateWithdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== AffiliateWithdrawalStatus::Approved) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['Withdraw harus disetujui sebelum ditandai dibayar.'],
                ]);
            }

            $locked->status = AffiliateWithdrawalStatus::Paid;
            $locked->paid_at = now();
            $locked->processed_by = $admin->id;
            if ($transferProofPath !== null) {
                $locked->transfer_proof_path = $transferProofPath;
            }
            $locked->save();

            app(AffiliateNotifier::class)->withdrawalPaid($locked);

            return $locked;
        });
    }

    public function markFailed(AffiliateWithdrawal $withdrawal, User $admin, ?string $reason = null): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason): AffiliateWithdrawal {
            /** @var AffiliateWithdrawal $locked */
            $locked = AffiliateWithdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [AffiliateWithdrawalStatus::Approved, AffiliateWithdrawalStatus::Requested], true)) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['Status withdraw tidak valid untuk ditandai gagal.'],
                ]);
            }

            $affiliate = Affiliate::query()->whereKey($locked->affiliate_id)->lockForUpdate()->firstOrFail();

            $this->credit(
                $affiliate,
                (float) $locked->amount,
                AffiliateWalletTransactionType::WithdrawRelease,
                'withdraw-fail-release:'.$locked->id,
                AffiliateWithdrawal::class,
                (string) $locked->id,
                'Withdraw gagal, saldo dikembalikan',
                ['reason' => $reason],
            );

            $locked->status = AffiliateWithdrawalStatus::Failed;
            $locked->failed_at = now();
            $locked->processed_by = $admin->id;
            $locked->failed_reason = filled($reason) ? trim($reason) : null;
            $locked->save();

            return $locked;
        });
    }
}
