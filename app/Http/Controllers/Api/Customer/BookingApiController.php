<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Services\BookingOrderCodeService;
use App\Support\PlatformFee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    public function show(Request $request, MuthowifBooking $booking): JsonResponse
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load(['muthowifProfile.user', 'bookingPayments']);

        return response()->json($booking);
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

                if (! $profile->isSlotAvailableForRange($start, $end)) {
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
                $pricingService = app(\App\Services\BookingPricingService::class);
                
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
                $ticketOutbound = $request->file('ticket_outbound')->store($dir, 'local');
                $ticketReturn = $request->file('ticket_return')->store($dir, 'local');
                $passport = $request->file('passport')->store($dir, 'local');
                $itineraryPath = $request->file('itinerary')?->store($dir, 'local');
                $visaPath = $request->file('visa')?->store($dir, 'local');

                $booking->update([
                    'ticket_outbound_path' => $ticketOutbound,
                    'ticket_return_path' => $ticketReturn,
                    'passport_path' => $passport,
                    'itinerary_path' => $itineraryPath,
                    'visa_path' => $visaPath,
                ]);

                return $booking->fresh();
            });

            return response()->json([
                'message' => 'Pemesanan berhasil dibuat',
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private const PAYMENT_METHODS = [
        'va_bca', 'va_bni', 'va_bri', 'va_permata', 'va_mandiri_bill',
        'qris', 'gopay', 'shopeepay',
    ];

    public function pay(Request $request, MuthowifBooking $booking): JsonResponse
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->isPaid()) {
            return response()->json(['message' => 'Pembayaran sudah diterima'], 400);
        }

        /** @var \App\Services\MidtransSnapService $midtrans */
        $midtrans = app(\App\Services\MidtransSnapService::class);

        if (! $midtrans->isConfigured()) {
            return response()->json(['message' => 'Sistem pembayaran belum dikonfigurasi'], 500);
        }

        $method = (string) $request->input('method', '');

        // Jika belum ada metode → kembalikan daftar metode saja
        if ($method === '') {
            return response()->json([
                'step'    => 'select_method',
                'methods' => self::PAYMENT_METHODS,
                'amount'  => (int) round($booking->resolvedAmountDue()),
            ]);
        }

        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            return response()->json(['message' => 'Metode pembayaran tidak didukung'], 422);
        }

        $baseInt = (int) round($booking->resolvedAmountDue());
        if ($baseInt < 1) {
            return response()->json(['message' => 'Total pembayaran tidak valid'], 400);
        }

        $split = PlatformFee::split((float) $baseInt);

        $booking->bookingPayments()->where('status', 'pending')->delete();

        $orderId = 'BG-'.str_replace('-', '', (string) $booking->getKey()).'-'.Str::lower(Str::random(10));

        $payment = BookingPayment::query()->create([
            'muthowif_booking_id' => $booking->getKey(),
            'order_id'            => $orderId,
            'gross_amount'        => (int) round($split['customer_gross']),
            'platform_fee_amount' => $split['platform_fee_total'],
            'muthowif_net_amount' => $split['muthowif_net'],
            'status'              => 'pending',
        ]);

        try {
            $session = $midtrans->createCoreChargeSession($payment, $method);

            $update = ['payment_type' => $method];
            if (! empty($session['transaction_id'])) {
                $update['midtrans_transaction_id'] = $session['transaction_id'];
            }
            $payment->update($update);

            return response()->json([
                'step'         => 'payment_instructions',
                'order_id'     => $orderId,
                'method'       => $method,
                'gross_amount' => $payment->gross_amount,
                'expiry_time'  => $session['expiry_time'],
                'va_bank'      => $session['va_bank'],
                'va_number'    => $session['va_number'],
                'bill_key'     => $session['bill_key'],
                'biller_code'  => $session['biller_code'],
                'qr_string'    => $session['qr_string'],
                'deeplink_url' => $session['deeplink_url'],
                'checkout_url' => $session['checkout_url'],
            ]);

        } catch (\Exception $e) {
            $payment->delete();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
