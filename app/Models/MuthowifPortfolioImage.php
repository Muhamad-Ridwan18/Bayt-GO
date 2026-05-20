<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifPortfolioImage extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_portfolio_id',
        'path',
        'original_name',
        'sort_order',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(MuthowifPortfolio::class, 'muthowif_portfolio_id');
    }
}
