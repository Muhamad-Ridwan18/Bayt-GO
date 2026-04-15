<?php

namespace App\Models;

use App\Enums\BookingChangeRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRescheduleRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'customer_id',
        'status',
        'previous_starts_on',
        'previous_ends_on',
        'new_starts_on',
        'new_ends_on',
        'customer_note',
        'muthowif_note',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingChangeRequestStatus::class,
            'previous_starts_on' => 'date',
            'previous_ends_on' => 'date',
            'new_starts_on' => 'date',
            'new_ends_on' => 'date',
            'decided_at' => 'datetime',
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
