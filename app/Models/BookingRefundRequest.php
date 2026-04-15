<?php

namespace App\Models;

use App\Enums\BookingChangeRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRefundRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'customer_id',
        'status',
        'customer_note',
        'refund_bank_name',
        'refund_account_holder',
        'refund_account_number',
        'muthowif_note',
        'admin_note',
        'service_base_amount',
        'customer_paid_amount',
        'refund_fee_platform',
        'refund_fee_muthowif',
        'net_refund_customer',
        'midtrans_refund_key',
        'midtrans_refunded_at',
        'midtrans_refund_response',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingChangeRequestStatus::class,
            'service_base_amount' => 'decimal:2',
            'decided_at' => 'datetime',
            'midtrans_refunded_at' => 'datetime',
            'midtrans_refund_response' => 'array',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'muthowif_booking_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === BookingChangeRequestStatus::Pending;
    }
}
