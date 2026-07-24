<?php

namespace App\Models;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'code',
        'status',
        'available_balance',
        'activated_at',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliateStatus::class,
            'available_balance' => 'decimal:2',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(AffiliateBankAccount::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(AffiliateWalletTransaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(AffiliateWithdrawal::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MuthowifBooking::class, 'affiliate_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function isActive(): bool
    {
        return $this->status === AffiliateStatus::Active;
    }

    /**
     * Total base transaksi booking beratribusi (pending + available). Void tidak dihitung.
     */
    public function attributedVolume(): float
    {
        return round((float) $this->commissions()
            ->whereIn('status', [
                AffiliateCommissionStatus::Pending->value,
                AffiliateCommissionStatus::Available->value,
            ])
            ->sum('transaction_base_amount_snapshot'), 2);
    }
}
