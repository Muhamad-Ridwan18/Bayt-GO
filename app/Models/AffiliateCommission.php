<?php

namespace App\Models;

use App\Enums\AffiliateCommissionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'affiliate_id',
        'muthowif_booking_id',
        'booking_payment_id',
        'customer_id',
        'affiliate_code_snapshot',
        'commission_rate_snapshot',
        'transaction_base_amount_snapshot',
        'platform_fee_amount_snapshot',
        'commission_amount',
        'status',
        'pending_at',
        'available_at',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate_snapshot' => 'decimal:6',
            'transaction_base_amount_snapshot' => 'decimal:2',
            'platform_fee_amount_snapshot' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'status' => AffiliateCommissionStatus::class,
            'pending_at' => 'datetime',
            'available_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'muthowif_booking_id');
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
