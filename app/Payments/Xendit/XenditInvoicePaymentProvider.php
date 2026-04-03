<?php

namespace App\Payments\Xendit;

use App\Enums\PaymentStatus;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\DTO\SnapPaymentSession as Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditInvoicePaymentProvider implements SnapPaymentProviderInterface
{
    public function isConfigured(): bool
    {
        $apiKey = config('services.xendit.api_key');

        return is_string($apiKey) && $apiKey !== '';
    }

    public function createPaymentSession(BookingPayment $payment): Session
    {
        $apiKey = config('services.xendit.api_key');
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('XENDIT_API_KEY belum diatur.');
        }

        $payment->loadMissing(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user']);

        /** @var MuthowifBooking $booking */
        $booking = $payment->muthowifBooking;
        $customer = $booking->customer;
        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';

        // Xendit expects invoice amount as number without thousands separator.
        $amount = (int) $payment->gross_amount;

        $baseUrl = rtrim((string) config('services.xendit.base_url', 'https://api.xendit.co'), '/');

        $payload = [
            'external_id' => $payment->order_id,
            'amount' => $amount,
            'payer_email' => $customer?->email,
            'description' => 'Pendampingan — '.$muthowifName,
            'success_redirect_url' => route('bookings.show', $booking).'?payment=success',
            'failure_redirect_url' => route('bookings.show', $booking).'?payment=failed',
            'invoice_duration' => 86400,
        ];

        $response = Http::timeout(45)
            ->withBasicAuth($apiKey, '')
            ->acceptJson()
            ->post($baseUrl.'/v2/invoices', $payload);

        if (! $response->successful()) {
            Log::warning('Xendit create invoice gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $payment->order_id,
            ]);

            throw new RuntimeException('Gagal membuat invoice Xendit.');
        }

        $json = $response->json();

        $invoiceUrl = $json['invoice_url'] ?? null;
        $invoiceId = $json['id'] ?? null;

        if (! is_string($invoiceUrl) || $invoiceUrl === '') {
            throw new RuntimeException('Respons Xendit tanpa invoice_url.');
        }

        return new SnapPaymentSession(
            snapToken: null,
            clientKey: null,
            snapJsUrl: null,
            paymentUrl: $invoiceUrl,
            providerReferenceId: is_string($invoiceId) && $invoiceId !== '' ? $invoiceId : $payment->order_id,
        );
    }

    public function handleNotification(Request $request): Response
    {
        $token = (string) ($request->header('x-callback-token') ?? '');
        $expected = (string) config('services.xendit.webhook_token');

        Log::debug('Xendit payment notification endpoint hit', [
            'has_token' => $token !== '',
        ]);

        if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        $event = (string) ($payload['event'] ?? '');
        Log::debug('Xendit payment notification payload parsed', [
            'event' => $event,
            'reference_id' => (is_string($payload['reference_id'] ?? null) ? (string) $payload['reference_id'] : null),
        ]);

        $referenceId = null;
        if (is_array(($payload['data']['data'] ?? null))) {
            $referenceId = $payload['data']['data']['reference_id'] ?? null;
        }
        if (! is_string($referenceId) || $referenceId === '') {
            // Fallback bentuk payload lain (beberapa event punya struktur data berbeda).
            $referenceId = $payload['reference_id'] ?? null;
        }

        if (! is_string($referenceId) || $referenceId === '') {
            return response('Missing reference_id', 400);
        }

        if ($event === '' || ! str_contains(strtolower($event), 'succeeded')) {
            return response('OK', 200);
        }

        $shouldNotifyMuthowif = false;
        DB::transaction(function () use ($referenceId, $payload, &$shouldNotifyMuthowif): void {
            /** @var BookingPayment|null $payment */
            $payment = BookingPayment::query()
                ->where('order_id', $referenceId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return;
            }

            // Idempotensi: jika sudah settled, jangan duplikasi.
            if (in_array($payment->status, ['settlement', 'capture'], true)) {
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

