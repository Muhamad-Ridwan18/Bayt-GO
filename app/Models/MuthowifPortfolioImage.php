<?php

namespace App\Models;

use App\Support\PublicMarketplaceMedia;
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

    protected static function booted(): void
    {
        static::saved(function (MuthowifPortfolioImage $image): void {
            if ($image->wasChanged('path')) {
                PublicMarketplaceMedia::syncPortfolioImage($image);
            }
        });

        static::deleted(function (MuthowifPortfolioImage $image): void {
            PublicMarketplaceMedia::removePortfolioImage($image);
        });
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(MuthowifPortfolio::class, 'muthowif_portfolio_id');
    }

    public function publicUrl(): string
    {
        if (! filled($this->path)) {
            return route('layanan.portfolio.image', $this);
        }

        if (MuthowifProfile::photoPathIsExternalUrl($this->path)) {
            return $this->path;
        }

        return PublicMarketplaceMedia::portfolioImageUrl($this)
            ?? route('layanan.portfolio.image', $this);
    }
}
