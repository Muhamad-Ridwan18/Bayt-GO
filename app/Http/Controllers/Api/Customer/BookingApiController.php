<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyAdminsOfRefundRequestSubmitted;
use App\Jobs\NotifyCustomerOfRescheduleSubmitted;
use App\Jobs\NotifyMuthowifOfRescheduleRequest;
use App\Models\BookingPayment;
use App\Models\BookingRescheduleRequest;
use App\Models\BookingReview;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Services\BookingNotificationDispatcher;
use App\Services\BookingCompletionService;
use App\Services\BookingOrderCodeService;
use App\Services\BookingPricingService;
use App\Services\BookingRefundExecutor;
use App\Services\Doku\DokuDirectChargeService;
use App\Services\Moota\MootaBookingChargeService;
use App\Services\SupportBookingService;
use App\Services\UploadedImageOptimizer;
use App\Support\ApiBookingDetail;
use App\Support\ApiEmergencyDetail;
use App\Support\ApiMootaPaymentMeta;
use App\Support\BookingPostPayRules;
use App\Support\BookingSnapPaymentCatalog;
use App\Support\CustomerBookingBroadcast;
use App\Support\MuthowifReferralReward;
use App\Support\PaymentFlowLog;
use App\Support\PlatformFee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingApiController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): JsonResponse
    {
        $bookings = MuthowifBooking::query()
            ->where('customer_id', $request->user()->id)
            ->with(['muthowifProfile.user'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $bookings->map(fn (MuthowifBooking $booking) => ApiBookingDetail::formatList($booking))->values(),
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): JsonResponse
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = ApiBookingDetail::format($booking);
        $data['can_report_emergency'] = $request->user()->can('reportEmergency', $booking);
        $data['can_request_support_completion'] = $request->user()->can('requestSupportCompletion', $booking);
        $data['emergency'] = ApiEmergencyDetail::for($booking);

        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            'passport' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
            'itinerary' => [
                Rule::requiredIf(fn () => $request->input('service_type') === 'group'),
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png',
                'max:10240',
            ],
            'visa' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'] ?? $validated['start_date'])->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return response()->json(['message' => 'Tanggal mulai tidak boleh sebelum hari ini.'], 422);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return response()->json(['message' => 'Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.'], 422);
        }

        $profileId = $validated['muthowif_profile_id'];
        $serviceType = MuthowifServiceType::from($validated['service_type']);

        try {
            $booking = DB::transaction(function () use ($request, $profileId, $start, $end, $validated, $serviceType): MuthowifBooking {
                /** @var MuthowifProfile $profile */
                $profile = MuthowifProfile::query()
                    ->with(['services.addOns'])
                    ->whereKey($profileId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $profile->isJadwalAvailableForRange($start, $end)) {
                    throw new \Exception('Jadwal muthowif tidak tersedia untuk rentang tanggal tersebut.');
                }

                $service = $profile->services->firstWhere('type', $serviceType);
                if (! $service) {
                    throw new \Exception('Tipe layanan tidak tersedia untuk muthowif ini.');
                }

                $min = $service->min_pilgrims !== null ? (int) $service->min_pilgrims : 1;
                $max = $service->max_pilgrims !== null ? (int) $service->max_pilgrims : 50;
                $count = (int) $validated['pilgrim_count'];
                if ($count < $min || $count > $max) {
                    throw new \Exception("Jumlah jamaah harus antara $min dan $max.");
                }

                $selectedAddOnIds = null;
                if ($serviceType === MuthowifServiceType::PrivateJamaah) {
                    $ids = array_values(array_unique($validated['add_on_ids'] ?? []));
                    $allowed = $service->addOns->pluck('id')->map(fn ($id) => (string) $id)->all();
                    foreach ($ids as $id) {
                        if (! in_array((string) $id, $allowed, true)) {
                            throw new \Exception('Salah satu add-on tidak valid.');
                        }
                    }
                    $selectedAddOnIds = $ids;
                }

                $withSameHotel = $request->boolean('with_same_hotel');
                $withTransport = $request->boolean('with_transport');

                if ($withSameHotel && (($service->same_hotel_price_per_day ?? null) === null || (float) $service->same_hotel_price_per_day <= 0)) {
                    throw new \Exception('Layanan hotel tidak tersedia.');
                }

                if ($withTransport && (($service->transport_price_flat ?? null) === null || (float) $service->transport_price_flat <= 0)) {
                    throw new \Exception('Layanan transportasi tidak tersedia.');
                }

                $bookingCode = app(BookingOrderCodeService::class)->allocateNextWithinTransaction();
                $pricingService = app(BookingPricingService::class);

                $booking = MuthowifBooking::query()->create(array_merge([
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
                    'with_same_hotel' => $withSameHotel,
                    'with_transport' => $withTransport,
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                ]))));

                $dir = 'booking-documents/'.$booking->getKey();
                $optimizer = app(UploadedImageOptimizer::class);
                $ticketOutbound = $optimizer->store($request->file('ticket_outbound'), $dir, 'local', 'document');
                $ticketReturn = $optimizer->store($request->file('ticket_return'), $dir, 'local', 'document');
                $passport = $optimizer->store($request->file('passport'), $dir, 'local', 'document');
                $itineraryFile = $request->file('itinerary');
                $visaFile = $request->file('visa');
                $itineraryPath = $itineraryFile ? $optimizer->store($itineraryFile, $dir, 'local', 'document') : null;
                $visaPath = $visaFile ? $optimizer->store($visaFile, $dir, 'local', 'document') : null;

                $booking->update([
                    'ticket_outbound_path' => $ticketOutbound,
                    'ticket_return_path' => $ticketReturn,
                    'passport_path' => $passport,
                    'itinerary_path' => $itineraryPath,
                    'visa_path' => $visaPath,
                ]);

                return $booking->fresh();
            });

            app(BookingNotificationDispatcher::class)->dispatchCreated($booking);

            return response()->json([
                'message' => 'Pemesanan berhasil dibuat',
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private const DOKU_PAYMENT_METHODS = [
        'va_bca', 'va_bni', 'va_bri', 'va_permata', 'va_mandiri_bill',
        'qris', 'gopay', 'shopeepay',
    ];

    /** @return list<string> */
    private function apiPaymentMethods(): array
    {
        return match (config('services.booking.payment_driver', 'doku')) {
            'moota' => BookingSnapPaymentCatalog::webMethodsExpanded(),
            default => self::DOKU_PAYMENT_METHODS,
        };
    }

    /** @param list<string> $methods */
    private function paymentMethodsMeta(array $methods): array
    {
        return ApiMootaPaymentMeta::methodsMeta($methods);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentEnvironmentPayload(): array
    {
        if (config('services.booking.payment_driver') !== 'moota') {
            return [];
        }

        return ApiMootaPaymentMeta::environment();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function methodMetaFor(string $method): ?array
    {
        foreach (ApiMootaPaymentMeta::methodsMeta([$method]) as $row) {
            if (($row['id'] ?? '') === $method) {
                return $row;
            }
        }

        return null;
    }

    public function pay(Request $request, MuthowifBooking $booking): JsonResponse
    {
        PaymentFlowLog::info('api.payment.enter', [
            'booking_id' => $booking->getKey(),
            'user_id' => $request->user()?->id,
            'method_body' => (string) $request->input('method', ''),
        ]);

        if ($booking->customer_id !== $request->user()->id) {
            PaymentFlowLog::warning('api.payment.unauthorized', ['booking_id' => $booking->getKey()]);

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->isPaid()) {
            PaymentFlowLog::info('api.payment.skip_already_paid', ['booking_id' => $booking->getKey()]);

            return response()->json(['message' => 'Pembayaran sudah diterima'], 400);
        }

        $driver = (string) config('services.booking.payment_driver', 'doku');
        $moota = app(MootaBookingChargeService::class);
        $doku = app(DokuDirectChargeService::class);

        $gatewayReady = $driver === 'moota'
            ? $moota->isConfigured()
            : $doku->isConfigured();

        if (! $gatewayReady) {
            PaymentFlowLog::warning('api.payment.gateway_unconfigured', [
                'booking_id' => $booking->getKey(),
                'driver' => $driver,
            ]);

            return response()->json(['message' => 'Sistem pembayaran belum dikonfigurasi'], 500);
        }

        $method = (string) $request->input('method', '');

        // Jika belum ada metode → kembalikan daftar metode saja
        if ($method === '') {
            $baseInt = (int) round($booking->resolvedAmountDue());
            $split = PlatformFee::split((float) $baseInt, $booking->customer?->isCompanyCustomer() ?? false);

            PaymentFlowLog::info('api.payment.select_method_step', [
                'booking_id' => $booking->getKey(),
                'amount' => (int) round($split['customer_gross']),
                'driver' => $driver,
            ]);

            return response()->json([
                'step' => 'select_method',
                'driver' => $driver,
                'methods' => $this->apiPaymentMethods(),
                'methods_meta' => $this->paymentMethodsMeta($this->apiPaymentMethods()),
                'amount' => (int) round($split['customer_gross']),
                'amounts' => [
                    'base' => (int) round($split['base']),
                    'platform_fee' => (int) round($split['customer_fee']),
                    'total' => (int) round($split['customer_gross']),
                ],
                'pricing' => ApiBookingDetail::pricing($booking),
                'payment_environment' => $this->paymentEnvironmentPayload(),
            ]);
        }

        if (! in_array($method, $this->apiPaymentMethods(), true)) {
            PaymentFlowLog::warning('api.payment.method_invalid', ['booking_id' => $booking->getKey(), 'method' => $method]);

            return response()->json(['message' => 'Metode pembayaran tidak didukung'], 422);
        }

        $mootaNormalizedForCharge = null;
        if ($driver === 'moota') {
            $mootaNormalizedForCharge = BookingSnapPaymentCatalog::normalizeWebPaymentMethod($method);
            if (str_starts_with($method, 'bank_transfer_moota__') && empty($mootaNormalizedForCharge['moota_bank_account_id'])) {
                PaymentFlowLog::warning('api.payment.moota_bad_account', ['booking_id' => $booking->getKey(), 'method' => $method]);

                return response()->json(['message' => __('bookings.flash.moota_invalid_account_choice')], 422);
            }
        }

        $baseInt = (int) round($booking->resolvedAmountDue());
        if ($baseInt < 1) {
            PaymentFlowLog::warning('api.payment.invalid_total', ['booking_id' => $booking->getKey(), 'base_int' => $baseInt]);

            return response()->json(['message' => 'Total pembayaran tidak valid'], 400);
        }

        $split = PlatformFee::split((float) $baseInt, $booking->customer?->isCompanyCustomer() ?? false);

        $superseded = $booking->bookingPayments()->where('status', 'pending')->update(['status' => 'cancelled']);
        if ($superseded > 0) {
            PaymentFlowLog::info('api.payment.supersede_pending', [
                'booking_id' => $booking->getKey(),
                'rows_cancelled' => $superseded,
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
            'gross_amount' => (int) round($split['customer_gross']),
            'platform_fee_amount' => $split['platform_fee_total'],
            'muthowif_net_amount' => $split['muthowif_net'],
            'referrer_muthowif_profile_id' => $referral['referrer_muthowif_profile_id'],
            'referral_reward_amount' => $referral['referral_reward_amount'],
            'status' => 'pending',
        ]);

        PaymentFlowLog::info('api.payment.charge_started', [
            'booking_id' => $booking->getKey(),
            'order_id' => $orderId,
            'driver' => $driver,
            'method' => $method,
            'gross_amount' => $payment->gross_amount,
            'booking_status' => $booking->status->value,
        ]);

        try {
            if ($driver === 'moota') {
                $mootaAccountIdRaw = $mootaNormalizedForCharge['moota_bank_account_id'] ?? null;
                $mootaExplicit = is_string($mootaAccountIdRaw) && $mootaAccountIdRaw !== '' ? trim($mootaAccountIdRaw) : null;
                $result = $moota->createChargeForBookingPayment($payment, $mootaExplicit);

                $gatewayPayload = [
                    'moota_create_transaction_response' => $result['payload'],
                    'moota_chosen_bank_account_id' => $result['bank_account_id'],
                ];
                if ($result['moota_total'] !== null) {
                    $gatewayPayload['moota_expected_transfer_total'] = $result['moota_total'];
                }

                $payment->update([
                    'gateway_transaction_id' => $result['trx_id'],
                    'gateway_notification_payload' => $gatewayPayload,
                    'payment_type' => 'bank_transfer_moota',
                ]);

                PaymentFlowLog::info('api.payment.session_ok', [
                    'booking_id' => $booking->getKey(),
                    'order_id' => $orderId,
                    'driver' => 'moota',
                    'have_checkout_url' => true,
                ]);

                return response()->json([
                    'step' => 'payment_instructions',
                    'driver' => 'moota',
                    'order_id' => $orderId,
                    'method' => $method,
                    'method_meta' => $this->methodMetaFor($method),
                    'gross_amount' => $payment->gross_amount,
                    'expected_transfer_total' => $result['moota_total'],
                    'trx_id' => $result['trx_id'],
                    'checkout_url' => $result['payment_url'],
                    'expiry_time' => $result['expiry_time'],
                    'payment_environment' => $this->paymentEnvironmentPayload(),
                ]);
            }

            $session = $doku->createChargeSession($payment, $method);

            $update = ['payment_type' => $method];
            if (! empty($session['transaction_id'])) {
                $update['gateway_transaction_id'] = $session['transaction_id'];
            }
            $payment->update($update);

            PaymentFlowLog::info('api.payment.session_ok', [
                'booking_id' => $booking->getKey(),
                'order_id' => $orderId,
                'driver' => 'doku',
                'method' => $method,
                'has_va' => ! empty($session['va_number']),
                'has_checkout_url' => ! empty($session['checkout_url']),
            ]);

            return response()->json([
                'step' => 'payment_instructions',
                'driver' => 'doku',
                'order_id' => $orderId,
                'method' => $method,
                'gross_amount' => $payment->gross_amount,
                'expiry_time' => $session['expiry_time'],
                'va_bank' => $session['va_bank'],
                'va_number' => $session['va_number'],
                'bill_key' => $session['bill_key'],
                'biller_code' => $session['biller_code'],
                'qr_string' => $session['qr_string'],
                'deeplink_url' => $session['deeplink_url'],
                'checkout_url' => $session['checkout_url'],
            ]);

        } catch (\Exception $e) {
            PaymentFlowLog::warning('api.payment.session_failed', [
                'booking_id' => $booking->getKey(),
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
            $payment->delete();

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function invoice(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('invoice', $booking);

        return response()->json(ApiBookingDetail::invoice($booking));
    }

    public function review(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('review', $booking);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        $text = $validated['comment'] ?? $validated['review'] ?? null;

        BookingReview::query()->updateOrCreate(
            ['muthowif_booking_id' => $booking->getKey()],
            [
                'muthowif_profile_id' => $booking->muthowif_profile_id,
                'customer_id' => $request->user()->id,
                'rating' => (int) $validated['rating'],
                'review' => filled($text) ? trim((string) $text) : null,
            ]
        );

        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json(['message' => 'Ulasan berhasil disimpan.']);
    }

    public function storeRefundRequest(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('requestPostPayRefund', $booking);

        if ($request->filled('reason') && ! $request->filled('customer_note')) {
            $request->merge(['customer_note' => $request->input('reason')]);
        }

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
            return response()->json(['message' => $block], 422);
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
            return response()->json(['message' => $e->getMessage()], 422);
        }

        NotifyAdminsOfRefundRequestSubmitted::afterRefundSubmitted((string) $refund->getKey());
        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json(['message' => 'Permintaan refund berhasil diajukan.']);
    }

    public function storeRescheduleRequest(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('requestPostPayReschedule', $booking);

        if ($request->filled('starts_on') && ! $request->filled('new_start_date')) {
            $request->merge(['new_start_date' => $request->input('starts_on')]);
        }

        if ($request->filled('reason') && ! $request->filled('reschedule_note')) {
            $request->merge(['reschedule_note' => $request->input('reason')]);
        }

        $validated = $request->validate([
            'new_start_date' => ['required', 'date'],
            'ends_on' => ['nullable', 'date'],
            'reschedule_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $block = BookingPostPayRules::canRequestReschedule($booking);
        if ($block !== null) {
            return response()->json(['message' => $block], 422);
        }

        $oldNights = $booking->billingNightsInclusive();
        if ($oldNights < 1) {
            return response()->json(['message' => __('bookings.validation.duration_invalid')], 422);
        }

        $start = Carbon::parse($validated['new_start_date'])->startOfDay();
        if (filled($validated['ends_on'] ?? null)) {
            $end = Carbon::parse($validated['ends_on'])->startOfDay();
        } else {
            $end = $start->copy()->addDays($oldNights - 1)->startOfDay();
        }

        $submittedAt = now();

        if ($start->lt($submittedAt->copy()->startOfDay())) {
            return response()->json(['message' => __('bookings.validation.new_start_past')], 422);
        }

        if (! BookingPostPayRules::newStartMeetsRescheduleMinDays($start, $submittedAt)) {
            return response()->json([
                'message' => __('bookings.validation.reschedule_new_start_too_soon', [
                    'days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
                ]),
            ], 422);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return response()->json([
                'message' => __('bookings.validation.new_range_max', ['days' => self::MAX_RANGE_DAYS]),
            ], 422);
        }

        $newNights = MuthowifBooking::inclusiveSpanDays($start, $end);
        if ($oldNights !== $newNights) {
            return response()->json(['message' => 'Jumlah hari pada pengajuan harus sama dengan booking.'], 422);
        }

        $booking->loadMissing('muthowifProfile');
        $profile = $booking->muthowifProfile;
        if ($profile === null || ! $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            return response()->json(['message' => __('bookings.validation.jadwal_baru_tidak_tersedia')], 422);
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

        return response()->json(['message' => 'Permintaan reschedule berhasil diajukan.']);
    }

    public function complete(Request $request, MuthowifBooking $booking, BookingCompletionService $completion): JsonResponse
    {
        $this->authorize('complete', $booking);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $text = $validated['review'] ?? $validated['comment'] ?? null;

        $result = $completion->complete(
            $booking,
            (int) $validated['rating'],
            filled($text) ? trim((string) $text) : null
        );

        if (! $result['completed']) {
            return response()->json(['message' => $result['error'] ?? 'Gagal menyelesaikan layanan.'], 422);
        }

        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json([
            'message' => $result['credited']
                ? 'Layanan selesai. Saldo muthowif telah dicatat.'
                : 'Layanan berhasil ditandai selesai.',
            'booking' => ApiBookingDetail::format($booking->fresh()),
        ]);
    }

    public function requestSupportCompletion(
        Request $request,
        MuthowifBooking $booking,
        SupportBookingService $support,
    ): JsonResponse {
        $this->authorize('requestSupportCompletion', $booking);

        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $support->requestCompletion($booking, (string) $request->user()->id);
        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json([
            'message' => __('layanan_pendukung.flash.completion_requested'),
            'booking' => ApiBookingDetail::format($booking->fresh()),
        ]);
    }

    public function cancel(Request $request, MuthowifBooking $booking): JsonResponse
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

        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan.',
            'booking' => ApiBookingDetail::format($booking->fresh()),
        ]);
    }
}
