<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'affiliate_id',
        'code_snapshot',
        'visitor_key',
        'ip_hash',
        'user_agent',
        'landing_path',
        'converted_booking_id',
        'converted_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function convertedBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'converted_booking_id');
    }
}
