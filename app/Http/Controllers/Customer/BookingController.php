<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfRescheduleSubmitted;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Jobs\NotifyMuthowifOfRescheduleRequest;
use App\Models\BookingPayment;
use App\Models\BookingRescheduleRequest;
use App\Models\BookingReview;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifServiceAddOn;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Services\BookingCompletionService;
use App\Services\BookingOrderCodeService;
use App\Services\BookingRefundExecutor;
use App\Support\BookingPostPayRules;
use App\Support\BookingRefundFee;
use App\Support\PlatformFee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    private const CORE_PAYMENT_METHODS = [
        'va_bca',
        'va_bni',
        'va_bri',
        'va_permata',
        'va_mandiri_bill',
        'qris',
        'gopay',
        'shopeepay',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MuthowifBooking::class);

        $bookings = $request->user()
            ->customerBookings()
            ->with(['muthowifProfile.user', 'muthowifProfile.services', 'review'])
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(15);

        $addonsById = $this->addOnsKeyById($bookings);

        $statusAggregates = MuthowifBooking::query()
            ->where('customer_id', $request->user()->getKey())
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $bookingStatusCounts = collect(BookingStatus::cases())->mapWithKeys(
            fn (BookingStatus $status) => [$status->value => (int) ($statusAggregates[$status->value] ?? 0)]
        );

        return view('bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $addonsById,
            'bookingStatusCounts' => $bookingStatusCounts,
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load([
            'muthowifProfile.user',
            'muthowifProfile.services.addOns',
            'review',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);
        $addonsById = $this->addOnsKeyByIdForBooking($booking);

        return view('bookings.show', [
            'booking' => $booking,
            'addonsById' => $addonsById,
            'refundEligibilityError' => BookingPostPayRules::canRequestRefund($booking),
            'rescheduleEligibilityError' => BookingPostPayRules::canRequestReschedule($booking),
            'refundPreview' => $this->refundPreviewForBooking($booking),
        ]);
    }

    public function payment(Request $request, MuthowifBooking $booking, SnapPaymentProviderInterface $provider): View|RedirectResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->isPaid()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', __('bookings.flash.payment_already_received'));
        }

        if (! $provider->isConfigured()) {
            return view('bookings.payment-unconfigured', [
                'booking' => $booking,
            ]);
        }

        $baseInt = (int) round($booking->resolvedAmountDue());
        if ($baseInt < 1) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', __('bookings.flash.invalid_total'));
        }

        $selectedMethod = (string) $request->query('method', '');
        if ($selectedMethod !== '' && ! in_array($selectedMethod, self::CORE_PAYMENT_METHODS, true)) {
            return redirect()
                ->route('bookings.payment', $booking)
                ->with('error', __('bookings.flash.method_not_supported'));
        }

        $split = PlatformFee::split((float) $baseInt);

        $booking->bookingPayments()->where('status', 'pending')->delete();

        $orderId = 'BG-'.str_replace('-', '', (string) $booking->getKey()).'-'.Str::lower(Str::random(10));

        $payment = BookingPayment::query()->create([
            'muthowif_booking_id' => $booking->getKey(),
            'order_id' => $orderId,
            // Nominal yang dibayar customer (base + fee customer).
            'gross_amount' => (int) round($split['customer_gross']),
            // Total biaya platform = fee customer + fee muthowif (masing-masing 7,5% dari base).
            'platform_fee_amount' => $split['platform_fee_total'],
            // Yang masuk ke saldo muthowif (base - fee muthowif).
            'muthowif_net_amount' => $split['muthowif_net'],
            'status' => 'pending',
        ]);

        $session = null;

        try {
            if ($selectedMethod !== '') {
                $session = $provider->createPaymentSession($payment, $selectedMethod);
            }

            if ($session !== null) {
                $update = [];
                if (! empty($session->snapToken)) {
                    $update['snap_token'] = $session->snapToken;
                }

                if (! empty($session->providerReferenceId)) {
                    $update['midtrans_transaction_id'] = $session->providerReferenceId;
                }

                if (is_string($selectedMethod) && $selectedMethod !== '') {
                    $update['payment_type'] = $selectedMethod;
                }

                if ($update !== []) {
                    $payment->update($update);
                }
            }
        } catch (RuntimeException $e) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        return view('bookings.payment', [
            'booking' => $booking,
            'payment' => $payment->fresh(),
            'selectedMethod' => $selectedMethod,
            'methods' => self::CORE_PAYMENT_METHODS,
            'instructions' => $session?->instructions,
        ]);
    }

    public function invoice(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('invoice', $booking);

        $booking->load(['muthowifProfile.user', 'customer']);
        $settled = $booking->settledBookingPayment();

        return view('bookings.invoice', [
            'booking' => $booking,
            'payment' => $settled,
        ]);
    }

    public function paymentStatus(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return response()->json([
            'booking_status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'is_paid' => $booking->isPaid(),
            'paid_at' => $booking->paid_at?->timezone(config('app.timezone'))?->toIso8601String(),
        ]);
    }

    /**
     * Customer menandai bahwa layanan sudah selesai.
     * Di titik ini barulah saldo muthowif ditambahkan (sekali saja).
     */
    public function complete(Request $request, MuthowifBooking $booking, BookingCompletionService $completion): RedirectResponse
    {
        $this->authorize('complete', $booking);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $completion->complete(
            $booking,
            (int) $validated['rating'],
            filled($validated['review'] ?? null) ? trim((string) $validated['review']) : null
        );

        if (! $result['completed']) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $result['error'] ?? __('bookings.flash.complete_error'));
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $result['credited'] ? __('bookings.flash.complete_credited') : __('bookings.flash.complete_ok'));
    }

    public function review(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('review', $booking);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        BookingReview::query()->updateOrCreate(
            ['muthowif_booking_id' => $booking->getKey()],
            [
                'muthowif_profile_id' => $booking->muthowif_profile_id,
                'customer_id' => $request->user()->id,
                'rating' => (int) $validated['rating'],
                'review' => filled($validated['review'] ?? null) ? trim((string) $validated['review']) : null,
            ]
        );

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('bookings.flash.review_saved'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MuthowifBooking::class);

        $validated = $request->validate([
            'muthowif_profile_id' => [
                'required',
                'uuid',
                Rule::exists('muthowif_profiles', 'id')->where(
                    fn ($q) => $q->where('verification_status', MuthowifVerificationStatus::Approved)
                ),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'service_type' => ['required', Rule::in(['group', 'private'])],
            'pilgrim_count' => ['required', 'integer', 'min:1', 'max:500'],
            'add_on_ids' => ['nullable', 'array'],
            'add_on_ids.*' => ['uuid'],
            'with_same_hotel' => ['sometimes', 'boolean'],
            'with_transport' => ['sometimes', 'boolean'],
            'ticket_outbound' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
            'ticket_return' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
            'itinerary' => [
                Rule::requiredIf(fn () => $request->input('service_type') === 'group'),
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png',
                'max:10240',
            ],
            'visa' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
        ], [
            'itinerary.required' => __('bookings.validation.itinerary_required_group'),
        ], [
            'ticket_outbound' => __('marketplace.panel.doc_ticket_outbound'),
            'ticket_return' => __('marketplace.panel.doc_ticket_return'),
            'itinerary' => __('marketplace.panel.doc_itinerary'),
            'visa' => __('marketplace.panel.doc_visa'),
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'] ?? $validated['start_date'])->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'start_date' => __('bookings.validation.start_past'),
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => __('bookings.validation.range_max', ['days' => self::MAX_RANGE_DAYS]),
            ]);
        }

        $profileId = $validated['muthowif_profile_id'];
        $serviceType = MuthowifServiceType::from($validated['service_type']);

        $booking = DB::transaction(function () use ($request, $profileId, $start, $end, $validated, $serviceType): MuthowifBooking {
            /** @var MuthowifProfile $profile */
            $profile = MuthowifProfile::query()
                ->with(['services.addOns'])
                ->whereKey($profileId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $profile->isSlotAvailableForRange($start, $end)) {
                throw ValidationException::withMessages([
                    'start_date' => __('bookings.validation.slot_unavailable'),
                ]);
            }

            $service = $profile->services->firstWhere('type', $serviceType);
            if (! $service) {
                throw ValidationException::withMessages([
                    'service_type' => [__('bookings.validation.service_unavailable')],
                ]);
            }

            [$min, $max] = self::pilgrimBounds($service);
            $count = (int) $validated['pilgrim_count'];
            if ($count < $min || $count > $max) {
                throw ValidationException::withMessages([
                    'pilgrim_count' => [__('bookings.validation.pilgrim_count_between', ['min' => $min, 'max' => $max])],
                ]);
            }

            $selectedAddOnIds = null;
            if ($serviceType === MuthowifServiceType::PrivateJamaah) {
                $ids = array_values(array_unique($validated['add_on_ids'] ?? []));
                $allowed = $service->addOns->pluck('id')->map(fn ($id) => (string) $id)->all();
                foreach ($ids as $id) {
                    if (! in_array((string) $id, $allowed, true)) {
                        throw ValidationException::withMessages([
                            'add_on_ids' => [__('bookings.validation.addon_invalid')],
                        ]);
                    }
                }
                $selectedAddOnIds = $ids;
            }

            $withSameHotel = $request->boolean('with_same_hotel');
            $withTransport = $request->boolean('with_transport');

            if ($withSameHotel && (($service->same_hotel_price_per_day ?? null) === null || (float) $service->same_hotel_price_per_day <= 0)) {
                throw ValidationException::withMessages([
                    'with_same_hotel' => [__('bookings.validation.hotel_unavailable')],
                ]);
            }

            if ($withTransport && (($service->transport_price_flat ?? null) === null || (float) $service->transport_price_flat <= 0)) {
                throw ValidationException::withMessages([
                    'with_transport' => [__('bookings.validation.transport_unavailable')],
                ]);
            }

            $bookingCode = app(BookingOrderCodeService::class)->allocateNextWithinTransaction();

            $booking = MuthowifBooking::query()->create([
                'booking_code' => $bookingCode,
                'muthowif_profile_id' => $profile->id,
                'customer_id' => $request->user()->id,
                'service_type' => $serviceType,
                'pilgrim_count' => $count,
                'selected_add_on_ids' => $selectedAddOnIds,
                'with_same_hotel' => $withSameHotel,
                'with_transport' => $withTransport,
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
                'status' => BookingStatus::Pending,
            ]);

            $dir = 'booking-documents/'.$booking->getKey();
            $ticketOutbound = $request->file('ticket_outbound')->store($dir, 'local');
            $ticketReturn = $request->file('ticket_return')->store($dir, 'local');
            $itineraryPath = $request->file('itinerary')?->store($dir, 'local');
            $visaPath = $request->file('visa')?->store($dir, 'local');

            $booking->update([
                'ticket_outbound_path' => $ticketOutbound,
                'ticket_return_path' => $ticketReturn,
                'itinerary_path' => $itineraryPath,
                'visa_path' => $visaPath,
            ]);

            return $booking->fresh();
        });

        NotifyMuthowifOfNewBooking::dispatchAfterResponse((string) $booking->getKey());

        return redirect()
            ->route('bookings.index')
            ->with('status', __('bookings.flash.booking_submitted'));
    }

    public function downloadDocument(Request $request, MuthowifBooking $booking, string $type): Response
    {
        $this->authorize('view', $booking);

        $column = match ($type) {
            'outbound' => 'ticket_outbound_path',
            'return' => 'ticket_return_path',
            'itinerary' => 'itinerary_path',
            'visa' => 'visa_path',
            default => null,
        };

        if ($column === null) {
            abort(404);
        }

        $path = $booking->{$column};
        if ($path === null || $path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return $disk->response($path, basename($path), [], $disposition);
    }

    public function storeRefundRequest(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('requestPostPayRefund', $booking);

        $validated = $request->validate([
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'refund_bank_name' => ['required', 'string', 'max:100'],
            'refund_account_holder' => ['required', 'string', 'max:255'],
            'refund_account_number' => ['required', 'string', 'max:64', 'regex:/^[\d\s\-]+$/'],
        ], [
            'refund_account_number.regex' => __('bookings.validation.refund_account_number_format'),
        ]);

        $block = BookingPostPayRules::canRequestRefund($booking);
        if ($block !== null) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $block);
        }

        $note = filled($validated['customer_note'] ?? null) ? trim((string) $validated['customer_note']) : null;
        $bankName = trim((string) $validated['refund_bank_name']);
        $accountHolder = trim((string) $validated['refund_account_holder']);
        $accountNumber = preg_replace('/\D+/', '', (string) $validated['refund_account_number']) ?? '';

        if ($accountNumber === '') {
            throw ValidationException::withMessages([
                'refund_account_number' => __('bookings.validation.refund_account_number_format'),
            ]);
        }

        try {
            app(BookingRefundExecutor::class)->execute(
                $booking,
                $request->user(),
                $note,
                $bankName,
                $accountHolder,
                $accountNumber,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('bookings.flash.refund_submitted'));
    }

    public function storeRescheduleRequest(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('requestPostPayReschedule', $booking);

        $validated = $request->validate([
            'new_start_date' => ['required', 'date'],
            'reschedule_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $block = BookingPostPayRules::canRequestReschedule($booking);
        if ($block !== null) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $block);
        }

        $oldNights = $booking->billingNightsInclusive();
        if ($oldNights < 1) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.duration_invalid'),
            ]);
        }

        $start = Carbon::parse($validated['new_start_date'])->startOfDay();
        $end = $start->copy()->addDays($oldNights - 1)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.new_start_past'),
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.new_range_max', ['days' => self::MAX_RANGE_DAYS]),
            ]);
        }

        $booking->loadMissing('muthowifProfile');
        $profile = $booking->muthowifProfile;
        if ($profile === null || ! $profile->isSlotAvailableForRange($start, $end, (string) $booking->getKey())) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.new_slot_unavailable'),
            ]);
        }

        $rescheduleRequest = BookingRescheduleRequest::query()->create([
            'muthowif_booking_id' => $booking->getKey(),
            'customer_id' => $request->user()->id,
            'status' => BookingChangeRequestStatus::Pending,
            'previous_starts_on' => $booking->starts_on->toDateString(),
            'previous_ends_on' => $booking->ends_on->toDateString(),
            'new_starts_on' => $start->toDateString(),
            'new_ends_on' => $end->toDateString(),
            'customer_note' => filled($validated['reschedule_note'] ?? null) ? trim((string) $validated['reschedule_note']) : null,
        ]);

        NotifyMuthowifOfRescheduleRequest::dispatchAfterResponse((string) $booking->getKey(), (string) $rescheduleRequest->getKey());
        NotifyCustomerOfRescheduleSubmitted::dispatchAfterResponse((string) $booking->getKey(), (string) $rescheduleRequest->getKey());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('bookings.flash.reschedule_submitted'));
    }

    public function cancel(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('cancelAsCustomer', $booking);

        DB::transaction(function () use ($booking): void {
            $booking->bookingPayments()->where('status', 'pending')->delete();
            $booking->update(['status' => BookingStatus::Cancelled]);
        });

        return redirect()
            ->route('bookings.index')
            ->with('status', __('bookings.flash.cancelled'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function pilgrimBounds(MuthowifService $service): array
    {
        $min = $service->min_pilgrims !== null ? (int) $service->min_pilgrims : 1;
        $max = $service->max_pilgrims !== null ? (int) $service->max_pilgrims : 50;
        $min = max(1, $min);
        if ($max < $min) {
            $max = $min;
        }

        return [$min, $max];
    }

    /**
     * @param  LengthAwarePaginator<int, MuthowifBooking>  $bookings
     * @return Collection<string, MuthowifServiceAddOn>
     */
    private function addOnsKeyById(LengthAwarePaginator $bookings): Collection
    {
        $ids = $bookings->getCollection()->flatMap(fn (MuthowifBooking $b) => $b->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * @return Collection<string, MuthowifServiceAddOn>
     */
    private function addOnsKeyByIdForBooking(MuthowifBooking $booking): Collection
    {
        $ids = collect($booking->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function refundPreviewForBooking(MuthowifBooking $booking): ?array
    {
        if (! $booking->isPaid() || $booking->status !== BookingStatus::Confirmed) {
            return null;
        }

        $payment = $booking->settledBookingPayment();
        if ($payment === null) {
            return null;
        }

        return BookingRefundFee::snapshot($booking, $payment);
    }
}
