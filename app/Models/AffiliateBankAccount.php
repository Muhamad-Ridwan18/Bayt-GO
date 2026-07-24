<?php

namespace App\Models;

use App\Enums\AffiliateBankVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateBankAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'affiliate_id',
        'bank_code',
        'bank_name',
        'account_holder',
        'account_number',
        'is_primary',
        'verification_status',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verification_status' => AffiliateBankVerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === AffiliateBankVerificationStatus::Verified;
    }
}
