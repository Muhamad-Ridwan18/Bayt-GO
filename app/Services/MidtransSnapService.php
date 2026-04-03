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

    public function snapBaseUrl(): string
    {
        return config('services.midtrans.is_production', false)
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    public function snapJsUrl(): string
    {
        return $this->snapBaseUrl().'/snap/snap.js';
    }

    /**
     * @throws RuntimeException
     */
    public function createSnapToken(BookingPayment $payment): string
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
                    'name' => Str::limit('Pendampingan — '.$muthowifName, 50),
                ],
            ],
            'callbacks' => [
                'finish' => route('bookings.show', $booking),
            ],
        ];

        $response = Http::timeout(45)
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($this->snapBaseUrl().'/snap/v1/transactions', $payload);

        if (! $response->successful()) {
            Log::warning('Midtrans Snap gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Gagal membuat sesi pembayaran Midtrans. Coba lagi atau hubungi admin.');
        }

        $token = $response->json('token');
        if (! is_string($token) || $token === '') {
            Log::warning('Midtrans Snap tanpa token', ['body' => $response->json()]);
            throw new RuntimeException('Respons Midtrans tidak valid.');
        }

        return $token;
    }
}
