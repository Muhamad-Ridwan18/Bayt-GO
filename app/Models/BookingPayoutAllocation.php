<?php

namespace App\Models;

use App\Enums\PayoutAllocationRole;
use App\Enums\PayoutAllocationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayoutAllocation extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_settlement_id',
        'muthowif_profile_id',
        'role',
        'service_days',
        'total_service_days',
        'amount',
        'status',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => PayoutAllocationRole::class,
            'status' => PayoutAllocationStatus::class,
            'amount' => 'decimal:2',
            'released_at' => 'datetime',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(BookingSettlement::class, 'booking_settlement_id');
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }
}
