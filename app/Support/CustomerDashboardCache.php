<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\SupportTicketStatus;
use App\Models\BookingReview;
use App\Models\MuthowifBooking;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

final class CustomerDashboardCache
{
    /**
     * @return array{
     *   activeBookingCount: int,
     *   supportOpenCount: int,
     *   upcomingTripCount: int,
     *   reviewsGivenCount: int,
     *   nextBooking: ?MuthowifBooking
     * }
     */
    public static function stats(User $user): array
    {
        $seconds = max(30, (int) config('marketplace.customer_dashboard_cache_seconds', 90));
        $userId = (string) $user->getKey();

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get('customer:dashboard:stats:'.$userId);
        if (is_array($cached)) {
            return self::hydrateStats($cached);
        }

        $payload = self::buildStatsPayload($userId);
        Cache::put('customer:dashboard:stats:'.$userId, $payload, now()->addSeconds($seconds));

        return self::hydrateStats($payload);
    }

    public static function forgetForUser(string|int $userId): void
    {
        Cache::forget('customer:dashboard:stats:'.$userId);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildStatsPayload(string $userId): array
    {
        $nextBooking = MuthowifBooking::query()
            ->where('customer_id', $userId)
            ->whereNotIn('status', [BookingStatus::Cancelled])
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->with(['muthowifProfile.user'])
            ->first();

        return [
            'activeBookingCount' => (int) MuthowifBooking::query()
                ->where('customer_id', $userId)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->count(),
            'supportOpenCount' => (int) SupportTicket::query()
                ->where('user_id', $userId)
                ->whereIn('status', [
                    SupportTicketStatus::Open,
                    SupportTicketStatus::InProgress,
                    SupportTicketStatus::AwaitingCustomer,
                ])
                ->count(),
            'upcomingTripCount' => (int) MuthowifBooking::query()
                ->where('customer_id', $userId)
                ->whereNotIn('status', [BookingStatus::Cancelled])
                ->whereDate('starts_on', '>=', now()->toDateString())
                ->count(),
            'reviewsGivenCount' => (int) BookingReview::query()
                ->where('customer_id', $userId)
                ->count(),
            'nextBooking' => $nextBooking ? [
                'booking' => $nextBooking->getAttributes(),
                'profile' => $nextBooking->muthowifProfile?->getAttributes(),
                'profile_user' => $nextBooking->muthowifProfile?->user?->getAttributes(),
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   activeBookingCount: int,
     *   supportOpenCount: int,
     *   upcomingTripCount: int,
     *   reviewsGivenCount: int,
     *   nextBooking: ?MuthowifBooking
     * }
     */
    private static function hydrateStats(array $payload): array
    {
        $nextBooking = null;
        if (is_array($payload['nextBooking'] ?? null)) {
            $row = $payload['nextBooking'];
            $nextBooking = (new MuthowifBooking)->newFromBuilder($row['booking']);
            $nextBooking->exists = true;

            if (is_array($row['profile'] ?? null)) {
                $profile = (new \App\Models\MuthowifProfile)->newFromBuilder($row['profile']);
                $profile->exists = true;
                if (is_array($row['profile_user'] ?? null)) {
                    $profileUser = (new User)->newFromBuilder($row['profile_user']);
                    $profileUser->exists = true;
                    $profile->setRelation('user', $profileUser);
                }
                $nextBooking->setRelation('muthowifProfile', $profile);
            }
        }

        return [
            'activeBookingCount' => (int) ($payload['activeBookingCount'] ?? 0),
            'supportOpenCount' => (int) ($payload['supportOpenCount'] ?? 0),
            'upcomingTripCount' => (int) ($payload['upcomingTripCount'] ?? 0),
            'reviewsGivenCount' => (int) ($payload['reviewsGivenCount'] ?? 0),
            'nextBooking' => $nextBooking,
        ];
    }
}
