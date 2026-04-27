<?php

namespace App\Models;

use App\Enums\MuthowifServiceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuthowifService extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'type',
        'name',
        'daily_price',
        'min_pilgrims',
        'max_pilgrims',
        'description',
        'same_hotel_price_per_day',
        'transport_price_flat',
    ];

    protected $appends = ['price'];

    protected function casts(): array
    {
        return [
            'type' => MuthowifServiceType::class,
            'daily_price' => 'decimal:2',
            'same_hotel_price_per_day' => 'decimal:2',
            'transport_price_flat' => 'decimal:2',
        ];
    }

    public function getPriceAttribute()
    {
        return $this->daily_price;
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(MuthowifServiceAddOn::class)->orderBy('sort_order');
    }

    public static function ensurePairForProfile(MuthowifProfile $profile): array
    {
        $group = static::firstOrCreate(
            [
                'muthowif_profile_id' => $profile->id,
                'type' => MuthowifServiceType::Group,
            ],
            []
        );

        $private = static::firstOrCreate(
            [
                'muthowif_profile_id' => $profile->id,
                'type' => MuthowifServiceType::PrivateJamaah,
            ],
            []
        );

        return [$group, $private];
    }
}
