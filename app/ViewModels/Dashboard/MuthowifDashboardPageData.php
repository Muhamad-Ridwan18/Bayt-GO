<?php

namespace App\ViewModels\Dashboard;

use App\Enums\BookingStatus;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\MuthowifDashboardCalendarDataBuilder;
use App\Support\IndonesianNumber;
use App\Support\MuthowifEmergencyOfferCounts;
use App\Support\WelcomeLanding;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class MuthowifDashboardPageData
{
    /**
     * @param  array<string, mixed>  $calendarData
     * @param  list<array{id: string, guest: string, date_label: string, service_label: string, status_label: string, status_pill: string, href: string}>  $upcomingBookings
     * @param  list<array{id: string, guest: string, month: string, day: string, service_label: string, status_label: string, status_pill: string}>  $weeklySchedule
     * @param  list<array{text: string, time_label: ?string, kind: string}>  $recentActivities
     * @param  array{guest: string, date_label: string, service_label: string, status_label: string, status_pill: string, href: string}|null  $nextBooking
     */
    public function __construct(
        public readonly MuthowifProfile $profile,
        public readonly string $userName,
        public readonly string $userEmail,
        public readonly string $userInitial,
        public readonly string $photoUrl,
        public readonly string $heroBgUrl,
        public readonly bool $hasServices,
        public readonly bool $missingWorkLocation,
        public readonly string $balanceFormatted,
        public readonly ?float $avgRating,
        public readonly int $reviewsCount,
        public readonly int $pendingBookingsCount,
        public readonly int $activeBookingsCount,
        public readonly int $pendingEmergencyOffersCount,
        public readonly int $unreadChatCount,
        public readonly array $calendarData,
        public readonly array $upcomingBookings,
        public readonly array $weeklySchedule,
        public readonly array $recentActivities,
        public readonly ?array $nextBooking,
    ) {}

    public static function for(User $user, ?string $monthParam = null): self
    {
        $mp = MuthowifProfile::query()
            ->whereKey($user->muthowifProfile->getKey())
            ->withMarketplaceStats()
            ->withCount([
                'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
                'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
            ])
            ->firstOrFail();

        $validServicesCount = $mp->services()
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->whereNotNull('daily_price')
            ->where('daily_price', '>', 0)
            ->count();

        $calendarData = MuthowifDashboardCalendarDataBuilder::build(
            $mp,
            is_string($monthParam) ? $monthParam : null
        );

        $upcoming = $mp->bookings()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->limit(3)
            ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
        $upcoming->load('customer:id,name');

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
        $weekly = $mp->bookings()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->whereDate('starts_on', '<=', $weekEnd->toDateString())
            ->whereDate('ends_on', '>=', $weekStart->toDateString())
            ->orderBy('starts_on')
            ->limit(12)
            ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
        $weekly->load('customer:id,name');

        $pendingBookingsCount = (int) $mp->pending_bookings_count;
        $activeBookingsCount = $pendingBookingsCount + (int) $mp->confirmed_bookings_count;

        $unreadChatCount = (int) BookingChatMessage::query()
            ->whereHas('muthowifBooking', fn ($q) => $q->where('muthowif_profile_id', $mp->getKey()))
            ->where('user_id', '!=', $user->getKey())
            ->whereNull('read_at')
            ->count();

        $upcomingCards = $upcoming->map(fn (MuthowifBooking $b) => self::mapBookingCard($b))->values()->all();
        $first = $upcoming->first();

        return new self(
            profile: $mp,
            userName: $user->name,
            userEmail: (string) $user->email,
            userInitial: mb_strtoupper(mb_substr($user->name, 0, 1)),
            photoUrl: $mp->photoUrl(),
            heroBgUrl: WelcomeLanding::resolvedHeroImageUrl(),
            hasServices: $validServicesCount > 0,
            missingWorkLocation: ! filled(trim((string) ($mp->work_location ?? ''))),
            balanceFormatted: IndonesianNumber::formatThousands((string) (int) round((float) ($mp->wallet_balance ?? 0))),
            avgRating: $mp->average_rating !== null ? round((float) $mp->average_rating, 1) : null,
            reviewsCount: (int) ($mp->booking_reviews_count ?? 0),
            pendingBookingsCount: $pendingBookingsCount,
            activeBookingsCount: $activeBookingsCount,
            pendingEmergencyOffersCount: MuthowifEmergencyOfferCounts::pendingOfferedCountForUser($user),
            unreadChatCount: $unreadChatCount,
            calendarData: $calendarData,
            upcomingBookings: $upcomingCards,
            weeklySchedule: $weekly->map(fn (MuthowifBooking $b) => self::mapWeeklyRow($b))->values()->all(),
            recentActivities: self::buildRecentActivities($mp, $user)->all(),
            nextBooking: $first ? self::mapBookingCard($first) : null,
        );
    }

    /**
     * @return array{id: string, guest: string, date_label: string, service_label: string, status_label: string, status_pill: string, href: string}
     */
    private static function mapBookingCard(MuthowifBooking $booking): array
    {
        return [
            'id' => (string) $booking->getKey(),
            'guest' => $booking->customer?->name ?? __('dashboard_muthowif.guest'),
            'date_label' => trim(
                ($booking->starts_on?->format('d') ?? '').'–'.($booking->ends_on?->format('d') ?? '').' '.($booking->starts_on?->translatedFormat('M Y') ?? '')
            ),
            'service_label' => $booking->service_type?->label() ?? '',
            'status_label' => $booking->status->label(),
            'status_pill' => self::statusPill($booking->status),
            'href' => route('muthowif.bookings.show', $booking),
        ];
    }

    /**
     * @return array{id: string, guest: string, month: string, day: string, service_label: string, status_label: string, status_pill: string}
     */
    private static function mapWeeklyRow(MuthowifBooking $booking): array
    {
        return [
            'id' => (string) $booking->getKey(),
            'guest' => $booking->customer?->name ?? __('dashboard_muthowif.guest'),
            'month' => $booking->starts_on?->translatedFormat('M') ?? '',
            'day' => $booking->starts_on?->format('d') ?? '',
            'service_label' => $booking->service_type?->label() ?? '',
            'status_label' => $booking->status->label(),
            'status_pill' => self::statusPill($booking->status),
        ];
    }

    private static function statusPill(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Pending => 'bg-amber-100 text-amber-950',
            BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * @return Collection<int, array{text: string, time_label: ?string, kind: string}>
     */
    private static function buildRecentActivities(MuthowifProfile $mp, User $user): Collection
    {
        $recentActivities = collect();

        $mp->bookings()
            ->with('customer:id,name')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'status', 'customer_id', 'updated_at'])
            ->each(function (MuthowifBooking $booking) use ($recentActivities) {
                $guest = $booking->customer?->name ?? __('dashboard_muthowif.guest');
                $text = match ($booking->status) {
                    BookingStatus::Confirmed => __('dashboard_muthowif.activity_confirmed', ['name' => $guest]),
                    BookingStatus::Pending => __('dashboard_muthowif.activity_pending', ['name' => $guest]),
                    default => __('dashboard_muthowif.activity_updated', ['name' => $guest]),
                };
                $recentActivities->push([
                    'text' => $text,
                    'time_label' => $booking->updated_at?->diffForHumans(),
                    'kind' => 'booking',
                    'sort' => $booking->updated_at?->timestamp ?? 0,
                ]);
            });

        BookingChatMessage::query()
            ->whereHas('muthowifBooking', fn ($q) => $q->where('muthowif_profile_id', $mp->getKey()))
            ->where('user_id', '!=', $user->getKey())
            ->with(['muthowifBooking.customer:id,name'])
            ->latest()
            ->limit(3)
            ->get()
            ->each(function (BookingChatMessage $message) use ($recentActivities) {
                $guest = $message->muthowifBooking?->customer?->name ?? __('dashboard_muthowif.guest');
                $recentActivities->push([
                    'text' => __('dashboard_muthowif.activity_chat', ['name' => $guest]),
                    'time_label' => $message->created_at?->diffForHumans(),
                    'kind' => 'chat',
                    'sort' => $message->created_at?->timestamp ?? 0,
                ]);
            });

        return $recentActivities
            ->sortByDesc('sort')
            ->take(6)
            ->values()
            ->map(fn (array $row) => [
                'text' => $row['text'],
                'time_label' => $row['time_label'],
                'kind' => $row['kind'],
            ]);
    }
}
