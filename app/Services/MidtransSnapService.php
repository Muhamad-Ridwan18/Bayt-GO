<?php

namespace App\Services;

use App\Models\BookingPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransSnapService
{
    public function isConfigured(): bool
    {
        $server = config('services.midtrans.server_key');
        $client = config('services.midtrans.client_key');

        return is_string($server) && $server !== ''
            && is_string($client) && $client !== '';
    }

    public function apiBaseUrl(): string
    {
        return config('services.midtrans.is_production', false)
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    public function snapBaseUrl(): string
    {
        return config('services.midtrans.is_production', false)
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    /**
     * @return array{
     *   token: string,
     *   redirect_url: string
     * }
     */
    public function createSnapSession(BookingPayment $payment): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        $payment->loadMissing(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user']);
        $booking = $payment->muthowifBooking;
        $customer = $booking->customer;
        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';

        $firstName = Str::limit(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', (string) ($customer?->name ?? 'Jamaah')), 40, '');

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) $payment->gross_amount,
            ],
            'customer_details' => array_filter([
                'first_name' => $firstName !== '' ? $firstName : 'Jamaah',
                'email' => $customer?->email,
                'phone' => $customer?->phone,
            ]),
            'item_details' => [
                [
                    'id' => 'BOOKING',
                    'price' => (int) $payment->gross_amount,
                    'quantity' => 1,
                    'name' => Str::limit('Pendampingan - '.$muthowifName, 50),
                ],
            ],
            // Optional: specify allowed payment methods if needed
            // 'enabled_payments' => ['credit_card', 'bca_va', 'bni_va', 'bri_va', 'mandiri_clickpay', 'gopay', 'shopeepay'],
        ];

        $response = Http::timeout(45)
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($this->snapBaseUrl().'/transactions', $payload);

        if (! $response->successful()) {
            Log::error('Midtrans Snap API gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);
            throw new RuntimeException('Gagal membuat sesi pembayaran Midtrans. ' . ($response->json('error_messages')[0] ?? ''));
        }

        return $response->json();
    }

    /**
     * @return array{
     *   transaction_id: string|null,
     *   payment_type: string|null,
     *   va_bank: string|null,
     *   va_number: string|null,
     *   bill_key: string|null,
     *   biller_code: string|null,
     *   qr_string: string|null,
     *   deeplink_url: string|null,
     *   checkout_url: string|null,
     *   expiry_time: string|null
     * }
     *
     * @throws RuntimeException
     */
    public function createCoreChargeSession(BookingPayment $payment, string $method): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        $payment->loadMissing(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user']);
        $booking = $payment->muthowifBooking;
        $customer = $booking->customer;
        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';

        $firstName = Str::limit(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', (string) ($customer?->name ?? 'Jamaah')), 40, '');

        $expiryMinutes = (int) config('services.midtrans.core_payment_expire_minutes', 60);
        if ($expiryMinutes < 1) {
            $expiryMinutes = 60;
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->gross_amount,
            ],
            'customer_details' => array_filter([
                'first_name' => $firstName !== '' ? $firstName : 'Jamaah',
                'email' => $customer?->email,
            ]),
            'item_details' => [
                [
                    'id' => 'BOOKING',
                    'price' => $payment->gross_amount,
                    'quantity' => 1,
                    'name' => Str::limit('Pendampingan - '.$muthowifName, 50),
                ],
            ],
            'custom_expiry' => [
                'order_time' => now()->format('Y-m-d H:i:s O'),
                'expiry_duration' => $expiryMinutes,
                'unit' => 'minute',
            ],
        ];

        switch ($method) {
            case 'va_bca':
            case 'va_bni':
            case 'va_bri':
                $payload['payment_type'] = 'bank_transfer';
                $payload['bank_transfer'] = ['bank' => str_replace('va_', '', $method)];
                break;
            case 'va_permata':
                $payload['payment_type'] = 'bank_transfer';
                $payload['bank_transfer'] = ['bank' => 'permata'];
                break;
            case 'va_mandiri_bill':
                $payload['payment_type'] = 'echannel';
                $payload['echannel'] = ['bill_info1' => 'Payment For', 'bill_info2' => 'BaytGo Booking'];
                break;
            case 'qris':
                $payload['payment_type'] = 'qris';
                $payload['qris'] = ['acquirer' => 'gopay'];
                break;
            case 'gopay':
                $payload['payment_type'] = 'gopay';
                break;
            case 'shopeepay':
                $payload['payment_type'] = 'shopeepay';
                break;
            default:
                throw new RuntimeException('Metode pembayaran tidak didukung.');
        }

        $response = Http::timeout(45)
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v2/charge', $payload);

        if (! $response->successful()) {
            Log::warning('Midtrans Core API charge gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Gagal membuat transaksi Midtrans Core API. Coba lagi atau hubungi admin.');
        }

        $responseJson = $response->json();
        $vaList = $responseJson['va_numbers'] ?? null;
        $vaNumber = null;
        $vaBank = null;
        if (is_array($vaList)) {
            foreach ($vaList as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (is_string($row['bank'] ?? null) && is_string($row['va_number'] ?? null)) {
                    $vaBank = $row['bank'];
                    $vaNumber = $row['va_number'];
                    break;
                }
            }
        }

        $transactionId = $response->json('transaction_id');
        $paymentType = $response->json('payment_type');
        $expiryTime = $response->json('expiry_time');
        $permataVa = $response->json('permata_va_number');
        $billKey = $response->json('bill_key');
        $billerCode = $response->json('biller_code');
        $actions = $response->json('actions');

        if ((! is_string($vaNumber) || $vaNumber === '') && is_string($permataVa) && $permataVa !== '') {
            $vaNumber = $permataVa;
            $vaBank = 'permata';
        }

        if (($method === 'va_mandiri_bill') && (! is_string($billKey) || ! is_string($billerCode) || $billKey === '' || $billerCode === '')) {
            Log::warning('Midtrans echannel tanpa bill key/code', ['body' => $responseJson]);
            throw new RuntimeException('Respons Midtrans tidak valid (bill key Mandiri tidak ditemukan).');
        }

        if (str_starts_with($method, 'va_') && ! in_array($method, ['va_mandiri_bill'], true) && (! is_string($vaNumber) || $vaNumber === '')) {
            Log::warning('Midtrans VA tanpa nomor VA', ['body' => $responseJson]);
            throw new RuntimeException('Respons Midtrans tidak valid (VA number tidak ditemukan).');
        }

        return [
            'transaction_id' => is_string($transactionId) && $transactionId !== '' ? $transactionId : null,
            'payment_type' => is_string($paymentType) && $paymentType !== '' ? $paymentType : 'bank_transfer',
            'va_bank' => $vaBank,
            'va_number' => $vaNumber,
            'bill_key' => is_string($billKey) && $billKey !== '' ? $billKey : null,
            'biller_code' => is_string($billerCode) && $billerCode !== '' ? $billerCode : null,
            'qr_string' => is_string($response->json('qr_string')) ? $response->json('qr_string') : null,
            'deeplink_url' => $this->findActionUrl($actions, ['deeplink-redirect', 'deeplink-redirect-app']),
            'checkout_url' => $this->findActionUrl($actions, ['generate-qr-code', 'deeplink-web-redirect']),
            'expiry_time' => is_string($expiryTime) && $expiryTime !== '' ? $expiryTime : null,
        ];
    }

    /**
     * @param  mixed  $actions
     * @param  array<int, string>  $names
     */
    /**
     * Refund transaksi yang sudah settlement. Endpoint: POST /v2/{order_id|transaction_id}/refund
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function refundTransaction(
        BookingPayment $payment,
        int $refundAmountIdr,
        string $refundKey,
        string $reason
    ): array {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        if ($refundAmountIdr < 1) {
            throw new RuntimeException('Nominal refund tidak valid.');
        }

        $chargeId = $this->refundPathIdentifier($payment);

        $payload = [
            'refund_key' => $refundKey,
            'amount' => $refundAmountIdr,
            'reason' => $reason !== '' ? $reason : 'Refund booking BaytGo',
        ];

        $encoded = rawurlencode($chargeId);
        $response = Http::timeout(60)
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v2/'.$encoded.'/refund', $payload);

        $body = $response->json();
        if (! is_array($body)) {
            Log::warning('Midtrans refund: respons bukan JSON', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Respons Midtrans refund tidak valid. Coba lagi atau hubungi admin.');
        }

        $statusCode = $body['status_code'] ?? null;
        if ($statusCode === '200') {
            return $body;
        }

        $message = is_string($body['status_message'] ?? null) ? $body['status_message'] : 'Refund ditolak Midtrans.';
        Log::warning('Midtrans refund gagal', [
            'http_status' => $response->status(),
            'midtrans_status' => $statusCode,
            'message' => $message,
            'order_id' => $payment->order_id,
        ]);

        throw new RuntimeException($message);
    }

    /**
     * Midtrans menerima order_id atau transaction_id di path. Untuk QRIS (terutama GoPay), gunakan transaction_id jika ada.
     */
    private function refundPathIdentifier(BookingPayment $payment): string
    {
        $type = strtolower((string) ($payment->payment_type ?? ''));

        if ($type === 'qris' && is_string($payment->midtrans_transaction_id) && $payment->midtrans_transaction_id !== '') {
            return $payment->midtrans_transaction_id;
        }

        return (string) $payment->order_id;
    }

    private function findActionUrl(mixed $actions, array $names): ?string
    {
        if (! is_array($actions)) {
            return null;
        }

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }
            $name = $action['name'] ?? null;
            $url = $action['url'] ?? null;
            if (is_string($name) && in_array($name, $names, true) && is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
    }
}

