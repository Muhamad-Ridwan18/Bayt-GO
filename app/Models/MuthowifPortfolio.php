<?php

namespace App\Models;

use App\Support\PublicMarketplaceMedia;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuthowifPortfolio extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'title',
        'description',
        'image_path',
        'sort_order',
    ];

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MuthowifPortfolioImage::class)->orderBy('sort_order')->orderBy('created_at');
    }

    public function coverImagePath(): ?string
    {
        $cover = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        return $cover?->path ?? $this->image_path;
    }

    public function coverUrl(): string
    {
        return PublicMarketplaceMedia::portfolioCoverUrl($this)
            ?? route('layanan.portfolio.photo', $this);
    }
}
