<?php

namespace App\Support;

use App\Models\MuthowifProfile;
use Illuminate\Support\Str;

class ApiMuthowifCard
{
    /**
     * @return array<string, mixed>
     */
    public static function fromProfile(MuthowifProfile $profile): array
    {
        $experiences = $profile->workExperiencesForDisplay();
        $primaryService = $profile->services->first();
        $startPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
        $avgRating = $profile->average_rating ?? $profile->booking_reviews_avg_rating ?? null;
        $reviewCount = (int) ($profile->booking_reviews_count ?? 0);

        $bio = filled($profile->reference_text)
            ? Str::limit(trim(strip_tags((string) $profile->reference_text)), 140)
            : null;

        return [
            'id' => $profile->id,
            'name' => $profile->user->name ?? 'Muthowif',
            'avatar' => ApiMediaUrl::muthowifAvatar($profile),
            'rating' => $avgRating !== null ? number_format((float) $avgRating, 1) : null,
            'reviews' => $reviewCount,
            'location' => $profile->workLocationLabel(),
            'start_price' => $startPrice,
            'languages' => array_slice($profile->languagesForDisplay(), 0, 3),
            'specialty' => $primaryService?->name,
            'experience' => $experiences[0] ?? null,
            'bio' => $bio,
        ];
    }
}
