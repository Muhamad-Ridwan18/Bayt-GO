<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingReplacementLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'booking_emergency_report_id',
        'from_muthowif_profile_id',
        'to_muthowif_profile_id',
        'chosen_by',
        'chosen_by_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(BookingEmergencyReport::class, 'booking_emergency_report_id');
    }
}
