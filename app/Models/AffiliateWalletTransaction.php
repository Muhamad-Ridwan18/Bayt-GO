<?php

namespace App\Models;

use App\Enums\AffiliateWalletTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateWalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'affiliate_id',
        'amount',
        'balance_after',
        'type',
        'source_type',
        'source_id',
        'idempotency_key',
        'description',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'type' => AffiliateWalletTransactionType::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }
}
