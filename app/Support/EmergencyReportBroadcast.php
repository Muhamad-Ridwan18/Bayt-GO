<?php

namespace App\Support;

use App\Events\EmergencyReportUpdated;
use App\Models\BookingEmergencyReport;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifProfile;
use Illuminate\Support\Facades\DB;

final class EmergencyReportBroadcast
{
    /**
     * @param  list<string>  $notifyUserIds
     */
    public static function afterResponse(
        BookingEmergencyReport|string $report,
        ?string $action = null,
        array $notifyUserIds = [],
    ): void {
        $reportId = (string) ($report instanceof BookingEmergencyReport ? $report->getKey() : $report);
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => trim((string) $id), $notifyUserIds),
            static fn (string $id): bool => $id !== '',
        )));

        if ($reportId === '') {
            return;
        }

        DB::afterCommit(static function () use ($reportId, $action, $ids): void {
            dispatch(static function () use ($reportId, $action, $ids): void {
                $report = BookingEmergencyReport::query()->find($reportId);
                if ($report !== null) {
                    broadcast(new EmergencyReportUpdated($report, $action, $ids));
                }
            })->afterResponse();
        });
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
