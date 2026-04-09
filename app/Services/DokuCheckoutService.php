<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Support\DokuSignature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DokuCheckoutService
{
    public function isConfigured(): bool
    {
        $client = config('services.doku.checkout_client_id');
        $secret = config('services.doku.checkout_shared_key');

        return is_string($client) && $client !== '' && is_string($secret) && $secret !== '';
    }

    public function apiBaseUrl(): string
    {
        return config('services.doku.checkout_is_sandbox', true)
            ? 'https://api-sandbox.doku.com'
            : 'https://api.doku.com';
    }

    /**
     * @return array{url: string, token_id: string|null, session_id: string|null}
     */
    public function createCheckout(BookingPayment $payment): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kredensial DOKU Checkout belum diatur.');
        }

        $payment->loadMissing(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user']);

        /** @var MuthowifBooking $booking */
        $booking = $payment->muthowifBooking;
        $customer = $booking->customer;
        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';

        $clientId = (string) config('services.doku.checkout_client_id');
        $sharedKey = (string) config('services.doku.checkout_shared_key');
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $requestTarget = '/checkout/v1/payment';

        $notifyUrl = url('/payments/doku/notification');
        $successUrl = route('bookings.show', $booking).'?payment=success';
        $failUrl = route('bookings.show', $booking).'?payment=failed';

        $payload = [
            'order' => [
                'amount' => (int) $payment->gross_amount,
                'invoice_number' => $payment->order_id,
                'currency' => 'IDR',
                'callback_url' => $successUrl,
                'callback_url_result' => $successUrl,
                'callback_url_cancel' => $failUrl,
                'language' => 'ID',
                'auto_redirect' => true,
            ],
            'payment' => [
                'payment_due_date' => (int) config('services.doku.checkout_payment_due_minutes', 1440),
            ],
            'customer' => array_filter([
                'email' => $customer?->email,
                'name' => $customer?->name,
            ]),
            'additional_info' => [
                'override_notification_url' => $notifyUrl,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Gagal menyiapkan body permintaan DOKU.');
        }

        $digest = DokuSignature::digest($body);
        $component = DokuSignature::componentString($clientId, $requestId, $timestamp, $requestTarget, $digest);
        $signature = DokuSignature::sign($component, $sharedKey);

        $response = Http::timeout(45)
            ->withHeaders([
                'Client-Id' => $clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => $signature,
            ])
            ->withBody($body, 'application/json')
            ->post($this->apiBaseUrl().$requestTarget);

        if (! $response->successful()) {
            Log::warning('DOKU Checkout create payment gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $payment->order_id,
            ]);
            throw new RuntimeException('Gagal membuat sesi pembayaran DOKU.');
        }

        $json = $response->json();
        $resp = is_array($json['response'] ?? null) ? $json['response'] : [];
        $pay = is_array($resp['payment'] ?? null) ? $resp['payment'] : [];

        $url = is_string($pay['url'] ?? null) ? $pay['url'] : null;
        if ($url === null || $url === '') {
            Log::warning('DOKU Checkout respons tanpa payment.url', ['body' => $response->body()]);
            throw new RuntimeException('Respons DOKU tidak valid.');
        }

        $tokenId = is_string($pay['token_id'] ?? null) ? $pay['token_id'] : null;
        $order = is_array($resp['order'] ?? null) ? $resp['order'] : [];
        $sessionId = is_string($order['session_id'] ?? null) ? $order['session_id'] : null;

        return [
            'url' => $url,
            'token_id' => $tokenId,
            'session_id' => $sessionId,
        ];
    }
}
