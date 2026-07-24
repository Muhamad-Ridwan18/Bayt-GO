<?php

namespace App\ViewModels\Layanan;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class LayananBookPageData
{
    public function __construct(
        public readonly MuthowifProfile $profile,
        public readonly BookingPanelData $panel,
        public readonly string $profileUrl,
        public readonly string $indexedUrl,
        public readonly ?string $searchRangeLabel,
        public readonly bool $canSubmit,
        public readonly mixed $group,
        public readonly mixed $private,
    ) {}

    /**
     * @param  array{can_submit: bool, reason: string|null, start: ?string, end: ?string}  $bookingIntent
     */
    public static function make(
        Request $request,
        MuthowifProfile $profile,
        array $bookingIntent,
        string $startDate,
        string $endDate,
    ): self {
        $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
        $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);

        $searchRangeLabel = null;
        if ($startDate !== '') {
            try {
                $endEff = $endDate !== '' ? $endDate : $startDate;
                $searchRangeLabel = Carbon::parse($startDate)->format('d/m/Y').' – '.Carbon::parse($endEff)->format('d/m/Y');
            } catch (\Throwable) {
                $searchRangeLabel = null;
            }
        }

        $listQs = array_filter([
            'q' => $request->query('q'),
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
            'service_type' => is_string($request->query('service_type')) && in_array($request->query('service_type'), ['group', 'private'], true)
                ? $request->query('service_type')
                : null,
            'pilgrim_count' => is_numeric($request->query('pilgrim_count')) && (int) $request->query('pilgrim_count') > 0
                ? (string) (int) $request->query('pilgrim_count')
                : null,
        ], fn ($v) => filled($v));

        $profileUrl = route('layanan.show', array_merge(['publicProfile' => $profile], $listQs));
        $indexedUrl = route('layanan.index', array_filter([
            'q' => $request->query('q'),
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
        ], fn ($v) => filled($v)));

        $canSubmit = ($bookingIntent['can_submit'] ?? false) && ($group || $private);

        $panel = BookingPanelData::make(
            $profile,
            $group,
            $private,
            $bookingIntent,
            $startDate,
            $endDate,
            $profileUrl,
            $searchRangeLabel,
            $indexedUrl,
            $canSubmit,
        );

        return new self(
            profile: $profile,
            panel: $panel,
            profileUrl: $profileUrl,
            indexedUrl: $indexedUrl,
            searchRangeLabel: $searchRangeLabel,
            canSubmit: $canSubmit,
            group: $group,
            private: $private,
        );
    }
}
