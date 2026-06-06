<?php

namespace App\Support;

use App\Events\EmergencyReportUpdated;
use App\Models\BookingEmergencyReport;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifProfile;

final class EmergencyReportBroadcast
{
    /**
     * @param  list<string>  $notifyUserIds
     */
    public static function notify(
        BookingEmergencyReport|string $report,
        ?string $action = null,
        array $notifyUserIds = [],
    ): void {
        $model = $report instanceof BookingEmergencyReport
            ? $report
            : BookingEmergencyReport::query()->find((string) $report);

        if ($model === null) {
            return;
        }

        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => trim((string) $id), $notifyUserIds),
            static fn (string $id): bool => $id !== '',
        )));

        ReverbBroadcast::send(new EmergencyReportUpdated($model, $action, $ids), 'emergency');
    }

    public static function afterResponse(
        BookingEmergencyReport|string $report,
        ?string $action = null,
        array $notifyUserIds = [],
    ): void {
        self::notify($report, $action, $notifyUserIds);
    }

    /**
     * @param  list<BookingReplacementOffer|string>  $offers
     */
    public static function notifyUserIdsFromOffers(array $offers): array
    {
        $profileIds = [];
        foreach ($offers as $offer) {
            $profileIds[] = (string) ($offer instanceof BookingReplacementOffer
                ? $offer->muthowif_profile_id
                : $offer);
        }

        $profileIds = array_values(array_unique(array_filter($profileIds)));

        if ($profileIds === []) {
            return [];
        }

        return MuthowifProfile::query()
            ->whereIn('id', $profileIds)
            ->pluck('user_id')
            ->map(static fn ($id) => (string) $id)
            ->all();
    }
}
