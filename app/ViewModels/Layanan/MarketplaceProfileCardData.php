<?php

namespace App\ViewModels\Layanan;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Support\IndonesianNumber;
use Illuminate\Support\Str;

final class MarketplaceProfileCardData
{
    public function __construct(
        public readonly MuthowifProfile $profile,
        public readonly ?MuthowifService $group,
        public readonly ?MuthowifService $private,
        public readonly string $profileHref,
        public readonly string $bookHref,
        public readonly string $fallbackSvg,
        public readonly ?string $specialization,
        public readonly ?string $experienceLine,
        public readonly ?int $minPrice,
        public readonly int $reviewsCount,
        public readonly ?string $avgRating,
        public readonly ?string $langsLine,
        public readonly ?string $workLocation,
        public readonly ?string $rangeLabel,
    ) {}

    public static function fromProfile(
        MuthowifProfile $profile,
        string $listQueryString = '',
        ?string $rangeLabel = null,
    ): self {
        $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
        $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
        $name = (string) ($profile->user->name ?? '—');

        $profileHref = route('layanan.show', $profile).($listQueryString !== '' ? '?'.$listQueryString : '');
        $bookHref = $listQueryString !== ''
            ? route('layanan.book', $profile).'?'.$listQueryString
            : $profileHref;

        if ($group && filled($group->description)) {
            $specialization = Str::limit(trim(strip_tags($group->description)), 72);
        } elseif ($private && filled($private->description)) {
            $specialization = Str::limit(trim(strip_tags($private->description)), 72);
        } else {
            $specialization = null;
        }

        $workLines = array_values(array_filter($profile->work_experiences ?? [], fn ($l) => filled($l)));
        $experienceLine = $workLines[0] ?? null;
        if ($experienceLine !== null) {
            $experienceLine = Str::limit(trim(strip_tags((string) $experienceLine)), 48);
        }

        $prices = [];
        if ($group && $group->daily_price !== null) {
            $prices[] = (int) $group->daily_price;
        }
        if ($private && $private->daily_price !== null) {
            $prices[] = (int) $private->daily_price;
        }
        $minPrice = count($prices) > 0 ? min($prices) : null;

        $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
        $avgRating = $profile->average_rating !== null
            ? number_format((float) $profile->average_rating, 1, ',', '')
            : null;
        $langs = array_slice($profile->languagesForDisplay(), 0, 4);
        $langsLine = $langs !== [] ? implode(', ', $langs) : null;

        return new self(
            profile: $profile,
            group: $group,
            private: $private,
            profileHref: $profileHref,
            bookHref: $bookHref,
            fallbackSvg: self::fallbackAvatarSvg($name),
            specialization: $specialization,
            experienceLine: $experienceLine,
            minPrice: $minPrice,
            reviewsCount: $reviewsCount,
            avgRating: $avgRating,
            langsLine: $langsLine,
            workLocation: $profile->workLocationLabel(),
            rangeLabel: $rangeLabel,
        );
    }

    public function minPriceFormatted(): ?string
    {
        return $this->minPrice !== null
            ? IndonesianNumber::formatThousands((string) $this->minPrice)
            : null;
    }

    private static function fallbackAvatarSvg(string $name): string
    {
        $initial = mb_substr($name, 0, 1);

        return 'data:image/svg+xml,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect fill="#e2e8f0" width="128" height="128"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="48" fill="#475569">'
            .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>'
        );
    }
}
