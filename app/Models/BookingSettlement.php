<?php

namespace App\Models;

use App\Enums\BookingSettlementStatus;
use App\Enums\BookingSettlementType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSettlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'booking_payment_id',
        'booking_incident_id',
        'settlement_type',
        'status',
        'calculation_snapshot',
        'approved_by_user_id',
        'approved_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_type' => BookingSettlementType::class,
            'status' => BookingSettlementStatus::class,
            'calculation_snapshot' => 'array',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class);
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(BookingIncident::class, 'booking_incident_id');
    }

    /**
     * @return HasMany<BookingPayoutAllocation, $this>
     */
    public function payoutAllocations(): HasMany
    {
        return $this->hasMany(BookingPayoutAllocation::class);
    }
}
