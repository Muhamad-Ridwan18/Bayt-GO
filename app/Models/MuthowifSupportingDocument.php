<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifSupportingDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'path',
        'original_name',
        'sort_order',
    ];

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }
}
