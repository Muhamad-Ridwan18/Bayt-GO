<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\MuthowifProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class MarketplaceSearchCache
{
    private const CACHE_VERSION = 'v1';

    public static function paginate(Request $request, string $startStr, string $endStr, string $q): LengthAwarePaginator
    {
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));
        $seconds = max(60, (int) config('marketplace.search_cache_seconds', 180));
        $key = sprintf(
            'marketplace:search:%s:%s:%s:%s:%d:%d',
            self::CACHE_VERSION,
            $startStr,
            $endStr,
            md5(mb_strtolower(trim($q))),
            $page,
            $perPage,
        );

        /** @var array{total: int, ids: list<string>} $snapshot */
        $snapshot = Cache::remember(
            $key,
            now()->addSeconds($seconds),
            static fn (): array => self::buildSnapshot($startStr, $endStr, $q, $page, $perPage),
        );

        return self::hydratePaginator($snapshot, $request, $perPage, $page);
    }

    /**
     * @return array{total: int, ids: list<string>}
     */
    private static function buildSnapshot(string $startStr, string $endStr, string $q, int $page, int $perPage): array
    {
        $paginator = self::availabilityQuery($startStr, $endStr, $q)
            ->paginate($perPage, ['id'], 'page', $page);

        return [
            'total' => (int) $paginator->total(),
            'ids' => $paginator->getCollection()->modelKeys(),
        ];
    }

    /**
     * @param  array{total: int, ids: list<string>}  $snapshot
     */
    private static function hydratePaginator(
        array $snapshot,
        Request $request,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $ids = $snapshot['ids'];
        if ($ids === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ]);
        }

        $rank = array_flip($ids);
        $profiles = MuthowifProfile::query()
            ->with(['user', 'services'])
            ->approved()
            ->hasPublishedServices()
            ->withMarketplaceStats()
            ->whereIn((new MuthowifProfile)->getQualifiedKeyName(), $ids)
            ->get()
            ->sortBy(static fn (MuthowifProfile $profile): int => $rank[$profile->getKey()] ?? PHP_INT_MAX)
            ->values();

        return new LengthAwarePaginator(
            $profiles,
            $snapshot['total'],
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return Builder<MuthowifProfile>
     */
    public static function availabilityQuery(string $startStr, string $endStr, string $q): Builder
    {
        $blockingStatuses = array_map(
            static fn (BookingStatus $status) => $status->value,
            BookingStatus::blocksAvailability(),
        );

        return MuthowifProfile::query()
            ->with(['user', 'services'])
            ->approved()
            ->hasPublishedServices()
            ->withMarketplaceStats()
            ->whereDoesntHave('blockedDates', function ($blocked) use ($startStr, $endStr): void {
                $blocked->whereBetween('blocked_on', [$startStr, $endStr]);
            })
            ->whereDoesntHave('bookings', function ($bookings) use ($startStr, $endStr, $blockingStatuses): void {
                $bookings->whereIn('status', $blockingStatuses)
                    ->where('starts_on', '<=', $endStr)
                    ->where('ends_on', '>=', $startStr);
            })
            ->when($q !== '', function ($query) use ($q): void {
                $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$q.'%'));
            })
            ->orderByMarketplaceRanking();
    }
}
