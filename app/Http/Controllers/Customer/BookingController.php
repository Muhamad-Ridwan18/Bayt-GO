<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifServiceAddOn;
use App\Support\PlatformFee;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

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
            ->with(['muthowifProfile.user'])
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(15);

        $addonsById = $this->addOnsKeyById($bookings);

        return view('bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $addonsById,
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load(['muthowifProfile.user', 'muthowifProfile.services.addOns']);
        $addonsById = $this->addOnsKeyByIdForBooking($booking);

        return view('bookings.show', [
            'booking' => $booking,
            'addonsById' => $addonsById,
        ]);
    }

    public function payment(Request $request, MuthowifBooking $booking, SnapPaymentProviderInterface $provider): View|RedirectResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->isPaid()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Pembayaran sudah diterima. Anda bisa cek invoice dari detail booking.');
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
                ->with('error', 'Total tagihan tidak valid. Hubungi muthowif atau admin.');
        }

        $selectedMethod = (string) $request->query('method', '');
        if ($selectedMethod !== '' && ! in_array($selectedMethod, self::CORE_PAYMENT_METHODS, true)) {
            return redirect()
                ->route('bookings.payment', $booking)
                ->with('error', 'Metode pembayaran tidak didukung.');
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
    public function complete(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('complete', $booking);

        $completed = false;
        $credited = false;
        $error = null;

        try {
            DB::transaction(function () use (
                $booking,
                &$completed,
                &$credited,
                &$error
            ): void {
                $booking->refresh();

                if ($booking->status === BookingStatus::Completed) {
                    $completed = true;
                    return;
                }

                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()
                    ->where('muthowif_booking_id', $booking->getKey())
                    ->whereIn('status', ['settlement', 'capture'])
                    ->orderByDesc('settled_at')
                    ->lockForUpdate()
                    ->first();

                if ($payment === null) {
                    $error = 'Transaksi pembayaran tidak ditemukan untuk booking ini.';
                    return;
                }

                if ($payment->wallet_credited_at === null) {
                    /** @var MuthowifProfile $profile */
                    $profile = MuthowifProfile::query()
                        ->whereKey($booking->muthowif_profile_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $profile->wallet_balance = round((float) $profile->wallet_balance + (float) $payment->muthowif_net_amount, 2);
                    $profile->save();

                    $payment->wallet_credited_at = now();
                    $payment->save();
                    $credited = true;
                }

                $booking->status = BookingStatus::Completed;
                $booking->save();

                $completed = true;
            });
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if (! $completed) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $error ?? 'Gagal menyelesaikan layanan. Coba lagi.');
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $credited ? 'Layanan selesai. Saldo muthowif Anda sudah diperbarui.' : 'Layanan selesai.');
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
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'] ?? $validated['start_date'])->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.',
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
                    'start_date' => 'Slot ini tidak lagi tersedia (libur atau sudah terisi). Perbarui pencarian Anda.',
                ]);
            }

            $service = $profile->services->firstWhere('type', $serviceType);
            if (! $service) {
                throw ValidationException::withMessages([
                    'service_type' => ['Layanan yang dipilih tidak tersedia untuk muthowif ini.'],
                ]);
            }

            [$min, $max] = self::pilgrimBounds($service);
            $count = (int) $validated['pilgrim_count'];
            if ($count < $min || $count > $max) {
                throw ValidationException::withMessages([
                    'pilgrim_count' => ["Jumlah jemaah harus antara {$min} dan {$max} untuk layanan ini."],
                ]);
            }

            $selectedAddOnIds = null;
            if ($serviceType === MuthowifServiceType::PrivateJamaah) {
                $ids = array_values(array_unique($validated['add_on_ids'] ?? []));
                $allowed = $service->addOns->pluck('id')->map(fn ($id) => (string) $id)->all();
                foreach ($ids as $id) {
                    if (! in_array((string) $id, $allowed, true)) {
                        throw ValidationException::withMessages([
                            'add_on_ids' => ['Satu atau lebih add-on tidak valid untuk layanan ini.'],
                        ]);
                    }
                }
                $selectedAddOnIds = $ids;
            }

            $withSameHotel = $request->boolean('with_same_hotel');
            $withTransport = $request->boolean('with_transport');

            if ($withSameHotel && (($service->same_hotel_price_per_day ?? null) === null || (float) $service->same_hotel_price_per_day <= 0)) {
                throw ValidationException::withMessages([
                    'with_same_hotel' => ['Opsi hotel tidak tersedia untuk layanan ini.'],
                ]);
            }

            if ($withTransport && (($service->transport_price_flat ?? null) === null || (float) $service->transport_price_flat <= 0)) {
                throw ValidationException::withMessages([
                    'with_transport' => ['Opsi transportasi tidak tersedia untuk layanan ini.'],
                ]);
            }

            return MuthowifBooking::query()->create([
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
        });

        NotifyMuthowifOfNewBooking::dispatchAfterResponse((string) $booking->getKey());

        return redirect()
            ->route('bookings.index')
            ->with('status', 'Permintaan booking dikirim. Tunggu persetujuan muthowif.');
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
            ->with('status', 'Booking dibatalkan.');
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
}
