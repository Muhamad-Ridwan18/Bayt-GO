<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyAdminsOfRefundRequestSubmitted;
use App\Jobs\NotifyCustomerOfRescheduleSubmitted;
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
use App\Services\BookingDocumentStore;
use App\Services\BookingNotificationDispatcher;
use App\Services\BookingOrderCodeService;
use App\Services\BookingPricingService;
use App\Services\BookingRefundExecutor;
use App\Services\Moota\MootaApiClient;
use App\Services\MuthowifNetworkReferralService;
use App\Support\BookingPostPayRules;
use App\Support\BookingRefundFee;
use App\Support\BookingSnapPaymentCatalog;
use App\Support\BookingPaymentReturn;
use App\Support\BookingWebLive;
use App\Support\CustomerBookingBroadcast;
use App\Support\EmergencyBookingViewData;
use App\Support\MuthowifReferralReward;
use App\Support\PaymentFlowLog;
use App\Support\PlatformFee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MuthowifBooking::class);

        $bookings = $this->customerBookingsIndexQuery($request)->paginate(15);

        $addonsById = $this->addOnsKeyById($bookings);

        $bookingStatusCounts = $this->customerBookingStatusCounts($request->user()->getKey());

        return view('bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $addonsById,
            'bookingStatusCounts' => $bookingStatusCounts,
            'statusFilter' => $this->resolveBookingStatusFilter($request),
        ]);
    }

    public function indexLiveFragment(Request $request): View
    {
        $this->authorize('viewAny', MuthowifBooking::class);

        $bookings = $this->customerBookingsIndexQuery($request)->paginate(15);

        $addonsById = $this->addOnsKeyById($bookings);

        $bookingStatusCounts = $this->customerBookingStatusCounts($request->user()->getKey());

        return view('bookings.partials.index-body', [
            'bookings' => $bookings,
            'addonsById' => $addonsById,
            'bookingStatusCounts' => $bookingStatusCounts,
            'statusFilter' => $this->resolveBookingStatusFilter($request),
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): View|RedirectResponse
    {
        $this->authorize('view', $booking);

        if ($redirect = BookingPaymentReturn::normalizeShowRedirect($request, $booking)) {
            return $redirect;
        }

        $booking->load([
            'muthowifProfile.user',
            'muthowifProfile.services.addOns',
            'supportPackage',
            'review',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);
        $addonsById = $this->addOnsKeyByIdForBooking($booking);

        $alternatives = $this->customerCancellationAlternatives($booking);

        return view('bookings.show', array_merge([
            'booking' => $booking,
            'addonsById' => $addonsById,
            'refundEligibilityError' => BookingPostPayRules::canRequestRefund($booking),
            'rescheduleEligibilityError' => BookingPostPayRules::canRequestReschedule($booking),
            'refundPreview' => $this->refundPreviewForBooking($booking),
            'referralNetworkAlternatives' => $alternatives['profiles'],
            'customerRecommendationSource' => $alternatives['source'],
            'showReferralNetworkPanel' => $alternatives['show_panel'],
        ], EmergencyBookingViewData::for($booking)));
    }

    public function showLiveState(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->refresh();

        return response()->json(BookingWebLive::customerShowState($booking, [
            'status' => $request->query('status'),
            'payment_status' => $request->query('payment_status'),
            'emergency_event' => $request->boolean('emergency_event'),
            'payment_return' => $request->boolean('payment_return'),
        ]));
    }

    public function showLiveFragment(Request $request, MuthowifBooking $booking): View
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

        $alternatives = $this->customerCancellationAlternatives($booking);

        return view('bookings.partials.show-body', array_merge([
            'booking' => $booking,
            'addonsById' => $addonsById,
            'refundEligibilityError' => BookingPostPayRules::canRequestRefund($booking),
            'rescheduleEligibilityError' => BookingPostPayRules::canRequestReschedule($booking),
            'refundPreview' => $this->refundPreviewForBooking($booking),
            'referralNetworkAlternatives' => $alternatives['profiles'],
            'customerRecommendationSource' => $alternatives['source'],
            'showReferralNetworkPanel' => $alternatives['show_panel'],
        ], EmergencyBookingViewData::for($booking)));
    }

    public function requestRefund(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('requestPostPayRefund', $booking);

        $booking->load(['muthowifProfile.user', 'muthowifProfile.services']);
        $error = BookingPostPayRules::canRequestRefund($booking);
        $preview = $this->refundPreviewForBooking($booking);

        return view('bookings.refund', [
            'booking' => $booking,
            'refundEligibilityError' => $error,
            'refundPreview' => $preview,
        ]);
    }

    public function requestReschedule(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('requestPostPayReschedule', $booking);

        $booking->load(['muthowifProfile.user', 'muthowifProfile.services']);
        $error = BookingPostPayRules::canRequestReschedule($booking);

        return view('bookings.reschedule', [
            'booking' => $booking,
            'rescheduleEligibilityError' => $error,
        ]);
    }

    public function payment(Request $request, MuthowifBooking $booking, SnapPaymentProviderInterface $provider): View|RedirectResponse
    {
        $this->authorize('pay', $booking);

        PaymentFlowLog::info('web.payment.enter', [
            'booking_id' => $booking->getKey(),
            'booking_status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'method_query' => (string) $request->query('method', ''),
            'provider' => get_class($provider),
        ]);

        if ($booking->isPaid()) {
            PaymentFlowLog::info('web.payment.skip_already_paid', ['booking_id' => $booking->getKey()]);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', __('bookings.flash.payment_already_received'));
        }

        if (! $provider->isConfigured()) {
            PaymentFlowLog::warning('web.payment.gateway_unconfigured', ['booking_id' => $booking->getKey()]);

            return view('bookings.payment-unconfigured', [
                'booking' => $booking,
            ]);
        }

        $baseInt = (int) round($booking->resolvedAmountDue());
        if ($baseInt < 1) {
            PaymentFlowLog::warning('web.payment.invalid_total', ['booking_id' => $booking->getKey(), 'base_int' => $baseInt]);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', __('bookings.flash.invalid_total'));
        }

        $selectedMethod = (string) $request->query('method', '');
        if ($selectedMethod !== '' && ! in_array($selectedMethod, BookingSnapPaymentCatalog::webMethodsExpanded(), true)) {
            PaymentFlowLog::warning('web.payment.method_not_supported', ['booking_id' => $booking->getKey(), 'method' => $selectedMethod]);

            return redirect()
                ->route('bookings.payment', $booking)
                ->with('error', __('bookings.flash.method_not_supported'));
        }

        $split = PlatformFee::split((float) $baseInt, $booking->customer?->isCompanyCustomer() ?? false);

        if ($selectedMethod === '') {
            $payment = $booking->bookingPayments()
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if ($payment === null) {
                $ids = BookingPayment::newPrimaryKeyAndOrderId((string) $booking->getKey());
                $orderId = $ids['order_id'];
                $referral = MuthowifReferralReward::paymentSnapshot(
                    (float) $split['muthowif_net'],
                    (string) $booking->muthowif_profile_id,
                );
                $payment = BookingPayment::query()->create([
                    'id' => $ids['id'],
                    'muthowif_booking_id' => $booking->getKey(),
                    'booking_code' => $booking->booking_code,
                    'order_id' => $orderId,
                    'gross_amount' => (int) round($split['customer_gross']),
                    'platform_fee_amount' => $split['platform_fee_total'],
                    'muthowif_net_amount' => $split['muthowif_net'],
                    'referrer_muthowif_profile_id' => $referral['referrer_muthowif_profile_id'],
                    'referral_reward_amount' => $referral['referral_reward_amount'],
                    'status' => 'pending',
                ]);
                PaymentFlowLog::info('web.payment.pending_created', [
                    'booking_id' => $booking->getKey(),
                    'order_id' => $orderId,
                    'gross_amount' => $payment->gross_amount,
                ]);
            } else {
                PaymentFlowLog::info('web.payment.pending_reused', [
                    'booking_id' => $booking->getKey(),
                    'order_id' => $payment->order_id,
                    'gross_amount' => $payment->gross_amount,
                ]);
            }

            return view('bookings.payment', [
                'booking' => $booking,
                'payment' => $payment->fresh(),
                'selectedMethod' => $selectedMethod,
                'methods' => BookingSnapPaymentCatalog::webMethodsExpanded(),
                'mootaBankAccountIds' => $this->mootaBankAccountIdsForPaymentView(),
                'mootaPaymentRows' => $this->mootaPaymentRowsForPaymentView(),
                'instructions' => null,
            ]);
        }

        if (BookingSnapPaymentCatalog::driver() === 'moota') {
            $expectedGross = (int) round($split['customer_gross']);
            $existingPending = $booking->bookingPayments()
                ->where('status', 'pending')
                ->where('payment_type', $selectedMethod)
                ->where('gross_amount', $expectedGross)
                ->whereNotNull('gateway_transaction_id')
                ->latest('id')
                ->first();

            if ($existingPending !== null) {
                $reuseInstructions = $this->mootaInstructionsFromStoredPayment($existingPending);
                if ($reuseInstructions !== null && ! empty($reuseInstructions['checkout_url'])) {
                    PaymentFlowLog::info('web.payment.moota_reuse_pending_session', [
                        'booking_id' => $booking->getKey(),
                        'order_id' => $existingPending->order_id,
                        'method' => $selectedMethod,
                    ]);

                    return view('bookings.payment', [
                        'booking' => $booking,
                        'payment' => $existingPending->fresh(),
                        'selectedMethod' => $selectedMethod,
                        'methods' => BookingSnapPaymentCatalog::webMethodsExpanded(),
                        'mootaBankAccountIds' => $this->mootaBankAccountIdsForPaymentView(),
                        'mootaPaymentRows' => $this->mootaPaymentRowsForPaymentView(),
                        'instructions' => $reuseInstructions,
                    ]);
                }
            }
        }

        $superseded = $booking->bookingPayments()->where('status', 'pending')->update(['status' => 'cancelled']);
        if ($superseded > 0) {
            PaymentFlowLog::info('web.payment.supersede_pending', [
                'booking_id' => $booking->getKey(),
                'rows_cancelled' => $superseded,
                'note' => 'Sesi bayar lama ditandai cancelled agar webhook DOKU tetap menemukan invoice jika user ganti metode.',
            ]);
        }

        $ids = BookingPayment::newPrimaryKeyAndOrderId((string) $booking->getKey());
        $orderId = $ids['order_id'];

        $referral = MuthowifReferralReward::paymentSnapshot(
            (float) $split['muthowif_net'],
            (string) $booking->muthowif_profile_id,
        );

        $payment = BookingPayment::query()->create([
            'id' => $ids['id'],
            'muthowif_booking_id' => $booking->getKey(),
            'booking_code' => $booking->booking_code,
            'order_id' => $orderId,
            // Nominal yang dibayar customer (base + fee customer).
            'gross_amount' => (int) round($split['customer_gross']),
            // Total biaya platform = fee customer + fee muthowif (masing-masing 7,5% dari base).
            'platform_fee_amount' => $split['platform_fee_total'],
            // Yang masuk ke saldo muthowif (base - fee muthowif).
            'muthowif_net_amount' => $split['muthowif_net'],
            'referrer_muthowif_profile_id' => $referral['referrer_muthowif_profile_id'],
            'referral_reward_amount' => $referral['referral_reward_amount'],
            'status' => 'pending',
        ]);

        PaymentFlowLog::info('web.payment.charge_started', [
            'booking_id' => $booking->getKey(),
            'order_id' => $orderId,
            'method' => $selectedMethod,
            'gross_amount' => $payment->gross_amount,
            'booking_status' => $booking->status->value,
        ]);

        $normalizedPay = BookingSnapPaymentCatalog::normalizeWebPaymentMethod($selectedMethod);

        if (str_starts_with($selectedMethod, 'bank_transfer_moota__') && empty($normalizedPay['moota_bank_account_id'])) {
            PaymentFlowLog::warning('web.payment.moota_bad_account_choice', ['booking_id' => $booking->getKey(), 'method' => $selectedMethod]);

            return redirect()
                ->route('bookings.payment', $booking)
                ->with('error', __('bookings.flash.moota_invalid_account_choice'));
        }

        $session = null;

        try {
            $mootaAccountIdRaw = $normalizedPay['moota_bank_account_id'] ?? null;
            $mootaAccountForProvider = is_string($mootaAccountIdRaw) && $mootaAccountIdRaw !== ''
                ? $mootaAccountIdRaw
                : null;
            $session = $provider->createPaymentSession(
                $payment,
                $normalizedPay['canonical'],
                $mootaAccountForProvider,
            );

            if ($session !== null) {
                $update = [];
                if (! empty($session->snapToken)) {
                    $update['checkout_token'] = $session->snapToken;
                }

                if (! empty($session->providerReferenceId)) {
                    $update['gateway_transaction_id'] = $session->providerReferenceId;
                }

                $update['payment_type'] = $selectedMethod;

                if ($update !== []) {
                    $payment->update($update);
                }
            }

            PaymentFlowLog::info('web.payment.session_ok', [
                'booking_id' => $booking->getKey(),
                'order_id' => $payment->order_id,
                'has_payment_url' => $session !== null && is_string($session->paymentUrl) && $session->paymentUrl !== '',
                'has_checkout_token' => $session !== null && ! empty($session->snapToken),
            ]);
        } catch (RuntimeException $e) {
            PaymentFlowLog::warning('web.payment.session_failed', [
                'booking_id' => $booking->getKey(),
                'order_id' => $payment->order_id ?? null,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        if ($session !== null && is_string($session->paymentUrl) && $session->paymentUrl !== '') {
            PaymentFlowLog::info('web.payment.redirect_gateway', [
                'booking_id' => $booking->getKey(),
                'order_id' => $payment->order_id,
                'payment_url_host' => parse_url($session->paymentUrl, PHP_URL_HOST),
            ]);

            return redirect()->away($session->paymentUrl);
        }

        PaymentFlowLog::info('web.payment.render_instructions', [
            'booking_id' => $booking->getKey(),
            'order_id' => $payment->order_id,
            'method' => $selectedMethod,
        ]);

        return view('bookings.payment', [
            'booking' => $booking,
            'payment' => $payment->fresh(),
            'selectedMethod' => $selectedMethod,
            'methods' => BookingSnapPaymentCatalog::webMethodsExpanded(),
            'mootaBankAccountIds' => $this->mootaBankAccountIdsForPaymentView(),
            'mootaPaymentRows' => $this->mootaPaymentRowsForPaymentView(),
            'instructions' => $session?->instructions,
        ]);
    }

    public function invoice(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('invoice', $booking);

        return $this->renderInvoice($booking);
    }

    public function signedInvoice(Request $request, MuthowifBooking $booking): View
    {
        if (! in_array($booking->payment_status, [
            PaymentStatus::Paid,
            PaymentStatus::RefundPending,
            PaymentStatus::Refunded,
        ], true)) {
            abort(404);
        }

        return $this->renderInvoice($booking);
    }

    private function renderInvoice(MuthowifBooking $booking): View
    {
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

        CustomerBookingBroadcast::afterResponse($booking->fresh());

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

        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('bookings.flash.review_saved'));
    }

    public function uploadTempDocument(Request $request): JsonResponse
    {
        $this->authorize('create', MuthowifBooking::class);

        $documentStore = app(BookingDocumentStore::class);

        $validated = $request->validate([
            'field' => ['required', 'string', Rule::in(BookingDocumentStore::FIELDS)],
            'file' => ['required', 'file', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024)],
            'previous_path' => ['nullable', 'string', 'max:500'],
        ]);

        $upload = $request->file('file');
        if (! $upload instanceof \Illuminate\Http\UploadedFile || ! $upload->isValid()) {
            return response()->json([
                'message' => __('bookings.validation.document_upload_failed'),
            ], 422);
        }

        try {
            $stored = $documentStore->storeTempUpload($upload, $validated['previous_path'] ?? null);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? __('bookings.validation.document_store_failed'),
            ], 422);
        }

        return response()->json($stored);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MuthowifBooking::class);

        $documentStore = app(BookingDocumentStore::class);

        $rules = array_merge([
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
        ], $documentStore->validationRules($request));

        $validator = Validator::make($request->all(), $rules, [
            'itinerary.required' => __('bookings.validation.itinerary_required_group'),
        ], [
            'ticket_outbound' => __('marketplace.panel.doc_ticket_outbound'),
            'ticket_return' => __('marketplace.panel.doc_ticket_return'),
            'passport' => __('marketplace.panel.doc_passport'),
            'itinerary' => __('marketplace.panel.doc_itinerary'),
            'visa' => __('marketplace.panel.doc_visa'),
        ]);

        if ($validator->fails()) {
            $documentStore->persistTempUploadsOnValidationFailure($request);

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

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

        $existing = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profileId)
            ->where('customer_id', $request->user()->id)
            ->where('starts_on', $start->toDateString())
            ->where('ends_on', $end->toDateString())
            ->where('status', BookingStatus::Pending)
            ->first();

        if ($existing) {
            return redirect()
                ->route('bookings.show', $existing)
                ->with('status', __('bookings.flash.booking_already_exists'));
        }

        $serviceType = MuthowifServiceType::from($validated['service_type']);

        $booking = DB::transaction(function () use ($request, $profileId, $start, $end, $validated, $serviceType): MuthowifBooking {
            /** @var MuthowifProfile $profile */
            $profile = MuthowifProfile::query()
                ->with(['services.addOns'])
                ->whereKey($profileId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $profile->isJadwalAvailableForRange($start, $end)) {
                throw ValidationException::withMessages([
                    'start_date' => __('bookings.validation.jadwal_tidak_tersedia'),
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

            $pricingService = app(BookingPricingService::class);

            return MuthowifBooking::query()->create(array_merge([
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
            ], $pricingService->getPricingSnapshots(new MuthowifBooking([
                'muthowif_profile_id' => $profile->id,
                'service_type' => $serviceType,
                'selected_add_on_ids' => $selectedAddOnIds,
            ]))));
        });

        try {
            $dir = 'booking-documents/'.$booking->getKey();
            $booking->update($documentStore->moveAllToBookingDirectory($request, $dir));
            $documentStore->assertRequiredDocumentsStored($booking->fresh(), $serviceType);
        } catch (ValidationException $e) {
            $booking->delete();

            throw $e;
        }

        app(BookingNotificationDispatcher::class)->dispatchCreated($booking);
        $this->forgetCustomerBookingStatusCounts((string) $request->user()->getKey());
        CustomerBookingBroadcast::afterResponse($booking);

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
            'passport' => 'passport_path',
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

        $filename = basename(str_replace('\\', '/', $path));
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        try {
            return $disk->response($path, $filename, [], $disposition);
        } catch (\Throwable) {
            return $disk->download($path, $filename, [
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            ]);
        }
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
            $refund = app(BookingRefundExecutor::class)->execute(
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

        NotifyAdminsOfRefundRequestSubmitted::afterRefundSubmitted((string) $refund->getKey());
        CustomerBookingBroadcast::afterResponse($booking->fresh());

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
        $submittedAt = now();

        if ($start->lt($submittedAt->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.new_start_past'),
            ]);
        }

        if (! BookingPostPayRules::newStartMeetsRescheduleMinDays($start, $submittedAt)) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.reschedule_new_start_too_soon', [
                    'days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
                ]),
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.new_range_max', ['days' => self::MAX_RANGE_DAYS]),
            ]);
        }

        $booking->loadMissing('muthowifProfile');
        $profile = $booking->muthowifProfile;
        if ($profile === null || ! $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            throw ValidationException::withMessages([
                'new_start_date' => __('bookings.validation.jadwal_baru_tidak_tersedia'),
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
        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('bookings.flash.reschedule_submitted'));
    }

    public function cancel(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('cancelAsCustomer', $booking);

        DB::transaction(function () use ($booking): void {
            $booking->bookingPayments()
                ->whereNotIn('status', ['settlement', 'capture'])
                ->delete();
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'muthowif_rejection_kind' => null,
                'muthowif_rejection_note' => null,
            ]);
        });

        $this->forgetCustomerBookingStatusCounts((string) $request->user()->getKey());
        CustomerBookingBroadcast::afterResponse($booking->fresh());

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
     * Instruksi bayar Moota dari payload charge yang sudah tersimpan (untuk reuse sesi tanpa supersede + cancel).
     *
     * @return array<string, mixed>|null
     */
    private function mootaInstructionsFromStoredPayment(BookingPayment $payment): ?array
    {
        $meta = $payment->gateway_notification_payload ?? [];
        if (! is_array($meta)) {
            return null;
        }

        $root = $meta['moota_create_transaction_response'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $data = $root['data'] ?? null;
        if (! is_array($data)) {
            $data = $root;
        }

        $url = data_get($data, 'payment_url');
        if (! is_string($url) || $url === '') {
            return null;
        }

        $exp = data_get($data, 'expired_at');
        $totalHint = $meta['moota_expected_transfer_total'] ?? data_get($data, 'total');

        return array_filter([
            'checkout_url' => $url,
            'expiry_time' => is_string($exp) && $exp !== '' ? $exp : null,
            'moota_expected_transfer_total' => is_numeric($totalHint) ? (int) round((float) $totalHint) : null,
        ], static fn ($v) => $v !== null && $v !== '');
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

    /** @return array<int, array{name: string, description: string}> */
    private function mootaPaymentRowsForPaymentView(): array
    {
        if (BookingSnapPaymentCatalog::driver() !== 'moota') {
            return [];
        }

        $ids = array_values(array_filter(array_map(trim(...), config('services.moota.bank_account_ids', []))));
        if ($ids === []) {
            return [];
        }

        return app(MootaApiClient::class)->paymentLabelsForOrderedAccountIds($ids);
    }

    /** @return list<string> */
    private function mootaBankAccountIdsForPaymentView(): array
    {
        if (BookingSnapPaymentCatalog::driver() !== 'moota') {
            return [];
        }

        return app(MootaApiClient::class)->bankAccountIds();
    }

    /**
     * @return array{
     *   profiles: Collection<int, MuthowifProfile>,
     *   source: string|null,
     *   show_panel: bool
     * }
     */
    private function customerCancellationAlternatives(MuthowifBooking $booking): array
    {
        $networkReferral = app(MuthowifNetworkReferralService::class);
        $showPanel = $networkReferral->shouldShowCustomerReferralPanel($booking);

        if (! $showPanel) {
            return [
                'profiles' => collect(),
                'source' => null,
                'show_panel' => false,
            ];
        }

        $resolved = $networkReferral->resolveCustomerAlternatives($booking);

        return [
            'profiles' => $this->referralAlternativesWithStats($resolved['profiles']),
            'source' => $resolved['source'],
            'show_panel' => true,
        ];
    }

    /**
     * @param  Collection<int, MuthowifProfile>  $profiles
     * @return Collection<int, MuthowifProfile>
     */
    private function referralAlternativesWithStats(Collection $profiles): Collection
    {
        if ($profiles->isEmpty()) {
            return $profiles;
        }

        $profiles->loadAvg('bookingReviews', 'rating');
        $profiles->loadCount('bookingReviews');

        return $profiles;
    }

    /**
     * @return array<string, int>
     */
    private function customerBookingsIndexQuery(Request $request)
    {
        $query = $request->user()
            ->customerBookings()
            ->with(['muthowifProfile.user', 'review'])
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at');

        $statusFilter = $this->resolveBookingStatusFilter($request);
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $query;
    }

    private function resolveBookingStatusFilter(Request $request): ?string
    {
        $status = $request->query('status');
        if (! is_string($status) || $status === '') {
            return null;
        }

        return in_array($status, array_map(
            static fn (BookingStatus $case) => $case->value,
            BookingStatus::cases(),
        ), true) ? $status : null;
    }

    private function forgetCustomerBookingStatusCounts(string $customerId): void
    {
        Cache::forget('customer_booking_status_counts:'.$customerId);
    }

    /**
     * @return array<string, int>
     */
    private function customerBookingStatusCounts(string $customerId): array
    {
        return Cache::remember(
            'customer_booking_status_counts:'.$customerId,
            now()->addSeconds(20),
            function () use ($customerId) {
                $statusAggregates = MuthowifBooking::query()
                    ->where('customer_id', $customerId)
                    ->toBase()
                    ->selectRaw('status, COUNT(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status');

                return collect(BookingStatus::cases())->mapWithKeys(
                    fn (BookingStatus $status) => [$status->value => (int) ($statusAggregates[$status->value] ?? 0)]
                )->all();
            },
        );
    }
}
