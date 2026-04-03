<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifServiceAddOn extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_service_id',
        'name',
        'price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function muthowifService(): BelongsTo
    {
        return $this->belongsTo(MuthowifService::class);
    }
}
