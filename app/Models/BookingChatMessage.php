<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['muthowif_booking_id', 'user_id', 'body', 'image_path', 'read_at'])]
class BookingChatMessage extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'muthowif_booking_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
