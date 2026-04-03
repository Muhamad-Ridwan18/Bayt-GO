<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifBlockedDate extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'blocked_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'blocked_on' => 'date',
        ];
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }
}
