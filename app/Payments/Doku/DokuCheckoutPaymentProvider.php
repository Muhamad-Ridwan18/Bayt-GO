<?php

namespace App\Payments\Doku;

use App\Enums\PaymentStatus;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\DTO\SnapPaymentSession;
use App\Services\DokuCheckoutService;
use App\Support\DokuSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DokuCheckoutPaymentProvider implements SnapPaymentProviderInterface
{
    public function __construct(
        private readonly DokuCheckoutService $checkout,
    ) {}

    public function isConfigured(): bool
    {
        return $this->checkout->isConfigured();
    }

    public function createPaymentSession(BookingPayment $payment): SnapPaymentSession
    {
        $created = $this->checkout->createCheckout($payment);

        return new SnapPaymentSession(
            snapToken: null,
            clientKey: null,
            snapJsUrl: null,
            paymentUrl: $created['url'],
            providerReferenceId: $created['token_id'] ?? $created['session_id'],
        );
    }

    public function handleNotification(Request $request): Response
    {
        $sharedKey = (string) config('services.doku.checkout_shared_key');
        $clientId = (string) config('services.doku.checkout_client_id');
        $path = $request->getPathInfo();
        if ($path === '') {
            $path = '/';
        }

        if (! DokuSignature::notificationValid($request, $sharedKey, $clientId, $path)) {
            Log::warning('DOKU payment notification: signature tidak valid');

            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        $order = is_array($payload['order'] ?? null) ? $payload['order'] : [];
        $transaction = is_array($payload['transaction'] ?? null) ? $payload['transaction'] : [];

        $invoiceNumber = is_string($order['invoice_number'] ?? null) ? $order['invoice_number'] : null;
        $amountRaw = $order['amount'] ?? null;
        $txStatus = is_string($transaction['status'] ?? null) ? $transaction['status'] : '';

        Log::debug('DOKU payment notification diterima', [
            'invoice_number' => $invoiceNumber,
            'transaction_status' => $txStatus,
        ]);

        if ($invoiceNumber === null || $invoiceNumber === '') {
            return response('Missing invoice_number', 400);
        }

        if (strtoupper($txStatus) !== 'SUCCESS') {
            return response('OK', 200);
        }

        $amountInt = (int) round((float) $amountRaw);
        $referenceId = $invoiceNumber;
        $shouldNotifyMuthowif = false;

        DB::transaction(function () use ($referenceId, $amountInt, $payload, &$shouldNotifyMuthowif): void {
            /** @var BookingPayment|null $payment */
            $payment = BookingPayment::query()
                ->where('order_id', $referenceId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return;
            }

            if (in_array($payment->status, ['settlement', 'capture'], true)) {
                return;
            }

            if ((int) $payment->gross_amount !== $amountInt) {
                Log::critical('DOKU notification: amount tidak cocok dengan catatan', [
                    'order_id' => $referenceId,
                    'expected' => $payment->gross_amount,
                    'got' => $amountInt,
                ]);

                return;
            }

            $payment->status = 'settlement';
            $payment->settled_at = now();
            $payment->midtrans_notification_payload = $payload;
            $payment->save();

            /** @var MuthowifBooking $booking */
            $booking = MuthowifBooking::query()
                ->whereKey($payment->muthowif_booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking->payment_status = PaymentStatus::Paid;
            $booking->paid_at = now();
            $booking->save();

            $shouldNotifyMuthowif = true;
        });

        if ($shouldNotifyMuthowif) {
            $payment = BookingPayment::query()->where('order_id', $referenceId)->first();
            if ($payment) {
                NotifyMuthowifOfPaidBooking::dispatchAfterResponse((string) $payment->muthowif_booking_id);
            }
        }

        return response('OK', 200);
    }
}
