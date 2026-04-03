<?php

namespace App\Payments\Midtrans;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\DTO\SnapPaymentSession;
use App\Services\MidtransSnapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransSnapPaymentProvider implements SnapPaymentProviderInterface
{
    public function __construct(
        private readonly MidtransSnapService $midtrans,
    ) {}

    public function isConfigured(): bool
    {
        return $this->midtrans->isConfigured();
    }

    public function createPaymentSession(BookingPayment $payment): SnapPaymentSession
    {
        $token = $this->midtrans->createSnapToken($payment);

        return new SnapPaymentSession(
            snapToken: $token,
            clientKey: (string) config('services.midtrans.client_key'),
            snapJsUrl: $this->midtrans->snapJsUrl(),
            paymentUrl: null,
            providerReferenceId: $payment->order_id,
        );
    }

    public function handleNotification(Request $request): Response
    {
        Log::debug('Midtrans notification endpoint hit', [
            'order_id' => $request->input('order_id'),
            'status_code' => $request->input('status_code'),
            'transaction_status' => $request->input('transaction_status'),
            'gross_amount' => $request->input('gross_amount'),
        ]);

        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            Log::warning('Midtrans notification: server key kosong');

            return response('OK', 200);
        }

        $orderId = $request->input('order_id');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');

        if (! is_string($orderId) || $orderId === '' || ! is_string($signatureKey)) {
            return response('Bad request', 400);
        }

        $expectedSig = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        if (! hash_equals($expectedSig, $signatureKey)) {
            Log::warning('Midtrans notification: signature tidak cocok', ['order_id' => $orderId]);

            return response('Invalid signature', 403);
        }

        $payload = $request->except(['signature_key']);
        $shouldNotifyMuthowif = false;

        try {
                $shouldNotifyMuthowif = DB::transaction(function () use ($orderId, $payload, $statusCode, $transactionStatus, $grossAmount, $request): bool {
                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()->where('order_id', $orderId)->lockForUpdate()->first();
                if ($payment === null) {
                    Log::warning('Midtrans notification: order_id tidak dikenal', ['order_id' => $orderId]);

                    return false;
                }

                /** @var MuthowifBooking $booking */
                $booking = MuthowifBooking::query()->whereKey($payment->muthowif_booking_id)->lockForUpdate()->firstOrFail();

                $payment->midtrans_notification_payload = $payload;
                if ($request->filled('transaction_id')) {
                    $payment->midtrans_transaction_id = (string) $request->input('transaction_id');
                }
                if ($request->filled('payment_type')) {
                    $payment->payment_type = (string) $request->input('payment_type');
                }

                $fraud = $request->input('fraud_status');
                if ($fraud === 'challenge') {
                    $payment->status = 'challenge';
                    $payment->save();

                    return false;
                }

                if (! in_array($transactionStatus, ['settlement', 'capture'], true)) {
                    if (is_string($transactionStatus) && $transactionStatus !== '') {
                        $payment->status = $transactionStatus;
                    }
                    $payment->save();

                    return false;
                }

                if ($statusCode !== '200') {
                    $payment->save();

                    return false;
                }

                $grossInt = (int) round((float) $grossAmount);
                if ($grossInt !== $payment->gross_amount) {
                    Log::critical('Midtrans notification: gross_amount tidak cocok dengan catatan', [
                        'order_id' => $orderId,
                        'expected' => $payment->gross_amount,
                        'got' => $grossInt,
                    ]);
                    throw new RuntimeException('Gross mismatch');
                }

                if ($booking->payment_status === PaymentStatus::Paid) {
                    $payment->status = is_string($transactionStatus) ? $transactionStatus : $payment->status;
                    $payment->settled_at = $payment->settled_at ?? now();
                    $payment->save();

                    return false;
                }

                if ($booking->status !== BookingStatus::Confirmed) {
                    Log::warning('Midtrans notification: booking tidak terkonfirmasi', ['booking_id' => $booking->id]);
                    $payment->status = is_string($transactionStatus) ? $transactionStatus : $payment->status;
                    $payment->save();

                    return false;
                }

                $booking->payment_status = PaymentStatus::Paid;
                $booking->paid_at = now();
                $booking->save();

                $payment->status = is_string($transactionStatus) ? $transactionStatus : 'settlement';
                $payment->settled_at = now();
                $payment->save();

                return true;
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Gross mismatch') {
                return response('Gross mismatch', 400);
            }

            throw $e;
        }

        if ($shouldNotifyMuthowif) {
            // Dispatch job agar kirim WA tidak memperlambat webhook.
            $payment = BookingPayment::query()->where('order_id', $orderId)->first();
            if ($payment) {
                NotifyMuthowifOfPaidBooking::dispatchAfterResponse((string) $payment->muthowif_booking_id);
            }
        }

        return response('OK', 200);
    }
}

