<?php

namespace App\Models;

use App\Enums\SupportPackageCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuthowifSupportPackage extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'name',
        'category',
        'description',
        'price',
        'min_pilgrims',
        'max_pilgrims',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category' => SupportPackageCategory::class,
            'price' => 'decimal:2',
            'min_pilgrims' => 'integer',
            'max_pilgrims' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MuthowifBooking::class, 'support_package_id');
    }

    public function pilgrimBounds(): array
    {
        $min = max(1, (int) $this->min_pilgrims);
        $max = max($min, (int) $this->max_pilgrims);

        return ['min' => $min, 'max' => $max];
    }
}
