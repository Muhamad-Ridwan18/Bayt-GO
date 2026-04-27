<?php

namespace App\Payments\Doku;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\CustomerBookingUpdated;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\DTO\SnapPaymentSession;
use App\Services\Doku\DokuCheckoutService;
use App\Services\Doku\DokuSignature;
use App\Support\PaymentFlowLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DokuCheckoutPaymentProvider implements SnapPaymentProviderInterface
{
    public function __construct(
        private readonly DokuCheckoutService $checkout,
    ) {}

    public function isConfigured(): bool
    {
        return $this->checkout->isConfigured();
    }

    public function createPaymentSession(BookingPayment $payment, ?string $method = null): SnapPaymentSession
    {
        if (! is_string($method) || $method === '') {
            throw new RuntimeException('Pilih metode pembayaran terlebih dahulu.');
        }

        $dokuType = DokuCheckoutService::checkoutMethodTypeForInternalMethod($method);
        if ($dokuType === null) {
            throw new RuntimeException('Metode pembayaran tidak didukung.');
        }

        $payment->loadMissing('muthowifBooking');
        $booking = $payment->muthowifBooking;
        if ($booking === null) {
            throw new RuntimeException('Booking tidak ditemukan.');
        }

        $callbackUrl = route('bookings.show', $booking);

        PaymentFlowLog::info('doku.provider.create_session', [
            'booking_id' => $booking->getKey(),
            'order_id' => $payment->order_id,
            'internal_method' => $method,
            'doku_type' => $dokuType,
            'gross_amount' => $payment->gross_amount,
            'callback_url' => $callbackUrl,
        ]);

        $created = $this->checkout->createCheckout($payment, [$dokuType], $callbackUrl);

        $snapJs = config('services.doku.is_production', false)
            ? 'https://jokul.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js'
            : 'https://sandbox.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js';

        return new SnapPaymentSession(
            snapToken: $created['token_id'],
            clientKey: (string) config('services.doku.client_id'),
            snapJsUrl: $snapJs,
            paymentUrl: $created['payment_url'],
            providerReferenceId: $payment->order_id,
            instructions: array_filter([
                'checkout_url' => $created['payment_url'],
                'expiry_time' => $created['expired_at']?->toIso8601String(),
            ]),
        );
    }

    public function handleNotification(Request $request): Response
    {
        $rawBody = $request->getContent();
        PaymentFlowLog::info('doku.webhook.received', [
            'content_length' => strlen($rawBody),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        $secret = (string) config('services.doku.secret_key');
        if ($secret === '') {
            Log::warning('DOKU notification: secret key kosong');
            PaymentFlowLog::warning('doku.webhook.abort_no_secret', []);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $path = (string) config('services.doku.notification_path', '/payments/doku/notification');
        if (! DokuSignature::notificationIsValid($request, $secret, $path)) {
            Log::warning('DOKU notification: signature tidak valid');
            PaymentFlowLog::warning('doku.webhook.signature_invalid', [
                'expected_request_target' => $path,
                'has_client_id' => $request->hasHeader('Client-Id'),
                'has_request_id' => $request->hasHeader('Request-Id'),
                'has_timestamp' => $request->hasHeader('Request-Timestamp'),
                'has_signature' => $request->hasHeader('Signature'),
            ]);

            return response('Invalid signature', 400)->header('Content-Type', 'text/plain');
        }

        PaymentFlowLog::info('doku.webhook.signature_ok', ['request_target' => $path]);

        $payload = $request->json()->all();
        if (! is_array($payload)) {
            PaymentFlowLog::warning('doku.webhook.body_not_json', []);

            return response('Bad request', 400)->header('Content-Type', 'text/plain');
        }

        PaymentFlowLog::info('doku.webhook.payload', PaymentFlowLog::dokuPayloadForLog($payload));

        $orderId = $this->extractInvoiceNumber($payload);
        if (! is_string($orderId) || $orderId === '') {
            Log::warning('DOKU notification: tidak ada invoice_number di payload', [
                'top_keys' => array_keys($payload),
            ]);
            PaymentFlowLog::warning('doku.webhook.no_invoice', ['top_keys' => array_keys($payload)]);

            return response('Bad request', 400)->header('Content-Type', 'text/plain');
        }

        $transactionStatus = $this->normalizeTransactionStatus($payload);
        PaymentFlowLog::info('doku.webhook.parsed', [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
        ]);

        if ($transactionStatus === 'FAILED') {
            PaymentFlowLog::info('doku.webhook.ignore_failed', ['order_id' => $orderId]);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        if ($transactionStatus !== 'SUCCESS') {
            PaymentFlowLog::info('doku.webhook.non_success_status', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus !== '' ? $transactionStatus : '(empty)',
            ]);
            try {
                $this->persistNonSuccess($orderId, $payload);
            } catch (\Throwable) {
                // ignore
            }

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $grossAmountRaw = data_get($payload, 'order.amount')
            ?? data_get($payload, 'response.order.amount')
            ?? data_get($payload, 'transaction.amount')
            ?? data_get($payload, 'response.transaction.amount');
        $grossInt = is_numeric($grossAmountRaw) ? (int) round((float) $grossAmountRaw) : 0;

        $shouldNotifyMuthowif = false;

        try {
            $shouldNotifyMuthowif = DB::transaction(function () use (
                $orderId,
                $payload,
                $grossInt,
            ): bool {
                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->first();
                if ($payment === null) {
                    Log::warning('DOKU notification: invoice tidak dikenal', ['order_id' => $orderId]);
                    PaymentFlowLog::warning('doku.webhook.payment_row_missing', [
                        'order_id' => $orderId,
                        'gross_from_payload' => $grossInt,
                    ]);

                    return false;
                }

                /** @var MuthowifBooking $booking */
                $booking = MuthowifBooking::query()
                    ->whereKey($payment->muthowif_booking_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                PaymentFlowLog::info('doku.webhook.rows_locked', [
                    'order_id' => $orderId,
                    'booking_id' => $booking->getKey(),
                    'booking_status' => $booking->status->value,
                    'payment_status' => $booking->payment_status->value,
                    'db_gross_amount' => $payment->gross_amount,
                    'payload_gross_amount' => $grossInt,
                ]);

                $payment->gateway_notification_payload = $payload;
                $trxId = data_get($payload, 'transaction.id')
                    ?? data_get($payload, 'response.transaction.id')
                    ?? data_get($payload, 'transaction_id')
                    ?? data_get($payload, 'uuid')
                    ?? data_get($payload, 'response.uuid');
                if (is_string($trxId) && $trxId !== '') {
                    $payment->gateway_transaction_id = $trxId;
                }
                $channel = data_get($payload, 'channel.id')
                    ?? data_get($payload, 'transaction.payment_channel');
                if (is_string($channel) && $channel !== '') {
                    $payment->payment_type = $channel;
                }

                if ($grossInt < 1 || $grossInt !== $payment->gross_amount) {
                    Log::critical('DOKU notification: amount tidak cocok', [
                        'order_id' => $orderId,
                        'expected' => $payment->gross_amount,
                        'got' => $grossInt,
                    ]);
                    PaymentFlowLog::warning('doku.webhook.gross_mismatch', [
                        'order_id' => $orderId,
                        'expected' => $payment->gross_amount,
                        'got' => $grossInt,
                    ]);
                    throw new RuntimeException('Gross mismatch');
                }

                if (in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true)) {
                    PaymentFlowLog::info('doku.webhook.skip_refund_state', [
                        'order_id' => $orderId,
                        'booking_payment_status' => $booking->payment_status->value,
                    ]);
                    $payment->status = 'settlement';
                    $payment->save();

                    return false;
                }

                if ($booking->payment_status === PaymentStatus::Paid) {
                    PaymentFlowLog::info('doku.webhook.already_paid', ['order_id' => $orderId]);
                    $payment->status = 'settlement';
                    $payment->settled_at = $payment->settled_at ?? now();
                    $payment->save();

                    return false;
                }

                if ($booking->status !== BookingStatus::Confirmed) {
                    Log::warning('DOKU notification: booking tidak terkonfirmasi', ['booking_id' => $booking->id]);
                    PaymentFlowLog::warning('doku.webhook.booking_not_confirmed', [
                        'order_id' => $orderId,
                        'booking_id' => $booking->id,
                        'booking_status' => $booking->status->value,
                    ]);
                    $payment->status = 'pending';
                    $payment->save();

                    return false;
                }

                PaymentFlowLog::info('doku.webhook.marking_paid', [
                    'order_id' => $orderId,
                    'booking_id' => $booking->id,
                ]);

                $booking->payment_status = PaymentStatus::Paid;
                $booking->paid_at = now();
                $booking->save();

                $payment->status = 'settlement';
                $payment->settled_at = now();
                $payment->save();

                return true;
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Gross mismatch') {
                PaymentFlowLog::warning('doku.webhook.response_gross_mismatch', ['order_id' => $orderId]);

                return response('Gross mismatch', 400)->header('Content-Type', 'text/plain');
            }

            throw $e;
        }

        if (! $shouldNotifyMuthowif) {
            PaymentFlowLog::info('doku.webhook.done_no_muthowif_notify', ['order_id' => $orderId]);
        }

        if ($shouldNotifyMuthowif) {
            PaymentFlowLog::info('doku.webhook.dispatch_notify_muthowif', ['order_id' => $orderId]);
            $payment = BookingPayment::query()->where('order_id', $orderId)->first();
            if ($payment) {
                NotifyMuthowifOfPaidBooking::dispatchAfterResponse((string) $payment->muthowif_booking_id);
                $booking = MuthowifBooking::query()->find($payment->muthowif_booking_id);
                if ($booking !== null) {
                    broadcast(new CustomerBookingUpdated($booking->fresh()));
                }
            }
        }

        PaymentFlowLog::info('doku.webhook.response_ok', ['order_id' => $orderId]);

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistNonSuccess(string $orderId, array $payload): void
    {
        $payment = BookingPayment::query()->where('order_id', $orderId)->first();
        if ($payment === null) {
            return;
        }
        $payment->gateway_notification_payload = $payload;
        $payment->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractInvoiceNumber(array $payload): ?string
    {
        foreach ([
            'order.invoice_number',
            'response.order.invoice_number',
            'invoice_number',
            'response.invoice_number',
        ] as $key) {
            $v = data_get($payload, $key);
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return null;
    }

    /**
     * DOKU Checkout notification dapat memakai bentuk datar atau bersarang di `response`, atau `message: ["SUCCESS"]`.
     */
    private function normalizeTransactionStatus(array $payload): string
    {
        foreach ([
            'transaction.status',
            'response.transaction.status',
            'status',
            'response.status',
        ] as $key) {
            $v = data_get($payload, $key);
            if (is_string($v) && $v !== '') {
                return strtoupper($v);
            }
        }

        $message = data_get($payload, 'message');
        if (is_array($message) && isset($message[0]) && is_string($message[0]) && $message[0] !== '') {
            return strtoupper($message[0]);
        }

        return '';
    }
}
