<?php

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MuthowifDirectoryController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $startRaw = $request->query('start_date');
        $endRaw = $request->query('end_date');

        $hasDateSearch = filled($startRaw);

        if (! $hasDateSearch) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => '',
                'endDate' => '',
                'hasDateSearch' => false,
                'dateErrors' => null,
                'rangeLabel' => null,
            ]);
        }

        $endEffective = filled($endRaw) ? $endRaw : $startRaw;

        $validator = Validator::make(
            [
                'start_date' => $startRaw,
                'end_date' => $endEffective,
            ],
            [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            [
                'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]
        );

        if ($validator->fails()) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => (string) $startRaw,
                'endDate' => (string) ($endRaw ?? ''),
                'hasDateSearch' => true,
                'dateErrors' => $validator->errors(),
                'rangeLabel' => null,
            ]);
        }

        $start = Carbon::parse($startRaw)->startOfDay();
        $end = Carbon::parse($endEffective)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'hasDateSearch' => true,
                'dateErrors' => new MessageBag(['start_date' => ['Tanggal mulai tidak boleh sebelum hari ini.']]),
                'rangeLabel' => null,
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'hasDateSearch' => true,
                'dateErrors' => new MessageBag(['end_date' => ['Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.']]),
                'rangeLabel' => null,
            ]);
        }

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $blockingStatuses = array_map(
            static fn (BookingStatus $s) => $s->value,
            BookingStatus::blocksAvailability()
        );

        $profiles = MuthowifProfile::query()
            ->with(['user', 'services'])
            ->withCount([
                'bookings as confirmed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Confirmed),
                'bookingReviews',
            ])
            ->withAvg('bookingReviews as average_rating', 'rating')
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->whereDoesntHave('blockedDates', function ($q) use ($startStr, $endStr) {
                $q->whereBetween('blocked_on', [$startStr, $endStr]);
            })
            ->whereDoesntHave('bookings', function ($q) use ($startStr, $endStr, $blockingStatuses) {
                $q->whereIn('status', $blockingStatuses)
                    ->where('starts_on', '<=', $endStr)
                    ->where('ends_on', '>=', $startStr);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
            })
            ->orderByDesc('verified_at')
            ->paginate(12)
            ->withQueryString();

        return view('layanan.index', [
            'profiles' => $profiles,
            'searchQuery' => $q,
            'startDate' => $startStr,
            'endDate' => $endStr,
            'hasDateSearch' => true,
            'dateErrors' => null,
            'rangeLabel' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ]);
    }

    public function show(Request $request, MuthowifProfile $publicProfile): View
    {
        $publicProfile->load([
            'user',
            'services.addOns',
            'bookingReviews' => fn ($q) => $q
                ->with('customer')
                ->latest()
                ->limit(10),
            'blockedDates' => fn ($q) => $q
                ->where('blocked_on', '>=', now()->toDateString())
                ->orderBy('blocked_on')
                ->limit(120),
        ]);
        $publicProfile->loadCount('bookingReviews');
        $publicProfile->loadAvg('bookingReviews', 'rating');

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile($request, $publicProfile, $startDate, $endDate);

        return view('layanan.show', [
            'profile' => $publicProfile,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'bookingIntent' => $bookingIntent,
        ]);
    }

    /**
     * @return array{can_submit: bool, reason: string|null, start: ?string, end: ?string}
     */
    private function bookingIntentForProfile(Request $request, MuthowifProfile $profile, string $startDate, string $endDate): array
    {
        $empty = ['can_submit' => false, 'reason' => null, 'start' => null, 'end' => null];

        $user = $request->user();
        if (! $user?->isCustomer()) {
            return array_merge($empty, [
                'reason' => $user ? 'not_customer' : 'guest',
                'start' => $startDate !== '' ? $startDate : null,
                'end' => $endDate !== '' ? $endDate : null,
            ]);
        }

        if ($startDate === '') {
            return array_merge($empty, ['reason' => 'missing_dates']);
        }

        $endEffective = $endDate !== '' ? $endDate : $startDate;

        $validator = Validator::make(
            ['start_date' => $startDate, 'end_date' => $endEffective],
            [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            ['end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.']
        );

        if ($validator->fails()) {
            return array_merge($empty, ['reason' => 'invalid_dates', 'start' => $startDate, 'end' => $endDate]);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endEffective)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return array_merge($empty, ['reason' => 'past_start', 'start' => $startDate, 'end' => $endEffective]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return array_merge($empty, ['reason' => 'range_too_long', 'start' => $startDate, 'end' => $endEffective]);
        }

        if (! $profile->isSlotAvailableForRange($start, $end)) {
            return array_merge($empty, [
                'reason' => 'slot_unavailable',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]);
        }

        return [
            'can_submit' => true,
            'reason' => null,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    public function photo(MuthowifProfile $publicProfile): Response
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($publicProfile->photo_path)) {
            abort(404);
        }

        return $disk->response($publicProfile->photo_path);
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'page',
        ]);
    }
}
