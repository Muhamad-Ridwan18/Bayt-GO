<?php

namespace App\Models;

use App\Enums\AffiliateWithdrawalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateWithdrawal extends Model
{
    use HasUuids;

    protected $fillable = [
        'affiliate_id',
        'affiliate_bank_account_id',
        'amount',
        'beneficiary_name',
        'beneficiary_account',
        'beneficiary_bank',
        'notes',
        'status',
        'requested_at',
        'approved_at',
        'rejected_at',
        'paid_at',
        'failed_at',
        'processed_by',
        'failed_reason',
        'transfer_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => AffiliateWithdrawalStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(AffiliateBankAccount::class, 'affiliate_bank_account_id');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
