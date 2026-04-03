<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'order_id',
        'gross_amount',
        'platform_fee_amount',
        'muthowif_net_amount',
        'status',
        'snap_token',
        'midtrans_transaction_id',
        'payment_type',
        'settled_at',
        'wallet_credited_at',
        'midtrans_notification_payload',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'platform_fee_amount' => 'decimal:2',
            'muthowif_net_amount' => 'decimal:2',
            'settled_at' => 'datetime',
            'wallet_credited_at' => 'datetime',
            'midtrans_notification_payload' => 'array',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'muthowif_booking_id');
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['settlement', 'capture'], true);
    }
}
