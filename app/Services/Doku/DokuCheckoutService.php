<?php

namespace App\Services\Doku;

use App\Models\BookingPayment;
use App\Support\PaymentFlowLog;
use Carbon\Carbon;
use RuntimeException;

final class DokuCheckoutService
{
    public function __construct(
        private readonly DokuApiClient $api,
    ) {}

    public function isConfigured(): bool
    {
        $client = config('services.doku.client_id');
        $secret = config('services.doku.secret_key');

        return is_string($client) && $client !== '' && is_string($secret) && $secret !== '';
    }

    /**
     * @param  list<string>|null  $paymentMethodTypes  DOKU checkout method codes; null = all enabled on dashboard
     * @return array{payment_url: string, token_id: ?string, expired_at: ?Carbon}
     */
    public function createCheckout(BookingPayment $payment, ?array $paymentMethodTypes = null, ?string $callbackUrl = null): array
    {
        $due = (int) config('services.doku.payment_due_minutes', 60);
        if ($due < 1) {
            $due = 60;
        }

        $order = [
            'amount' => (int) $payment->gross_amount,
            'invoice_number' => $payment->order_id,
            'currency' => 'IDR',
        ];
        if (is_string($callbackUrl) && $callbackUrl !== '') {
            $order['callback_url'] = $callbackUrl;
        }

        $paymentBlock = [
            'payment_due_date' => $due,
        ];
        if ($paymentMethodTypes !== null && $paymentMethodTypes !== []) {
            $paymentBlock['payment_method_types'] = array_values($paymentMethodTypes);
        }

        $notificationUrl = route('payments.doku.notification', absolute: true);
        $notifyHost = parse_url($notificationUrl, PHP_URL_HOST);
        if (is_string($notifyHost) && (in_array(strtolower($notifyHost), ['127.0.0.1', 'localhost'], true) || str_ends_with(strtolower($notifyHost), '.local'))) {
            PaymentFlowLog::warning('doku.checkout.notification_unreachable', [
                'notification_url' => $notificationUrl,
                'hint' => 'Server DOKU tidak bisa memanggil localhost. Set APP_URL ke URL publik (HTTPS + Cloudflare Tunnel / domain) agar webhook /payments/doku/notification terpanggil.',
            ]);
        }

        $payload = [
            'order' => $order,
            'payment' => $paymentBlock,
            'additional_info' => [
                'override_notification_url' => $notificationUrl,
            ],
        ];

        PaymentFlowLog::info('doku.checkout.request', [
            'invoice_number' => $payment->order_id,
            'gross_amount' => $payment->gross_amount,
            'payment_method_types' => $paymentMethodTypes,
            'callback_url' => $callbackUrl,
            'notification_url' => $notificationUrl,
            'api_base' => $this->api->baseUrl(),
        ]);

        $json = $this->api->postJson('/checkout/v1/payment', $payload);
        $inner = $json['response'] ?? $json;

        $url = data_get($inner, 'payment.url');
        if (! is_string($url) || $url === '') {
            PaymentFlowLog::warning('doku.checkout.missing_payment_url', [
                'invoice_number' => $payment->order_id,
                'response_keys' => is_array($inner) ? array_keys($inner) : [],
            ]);
            throw new RuntimeException('DOKU Checkout tidak mengembalikan payment URL.');
        }

        $tokenId = data_get($inner, 'payment.token_id');
        $expiredAt = $this->parseExpiredDate(data_get($inner, 'payment.expired_date'));

        PaymentFlowLog::info('doku.checkout.response_ok', [
            'invoice_number' => $payment->order_id,
            'payment_url_host' => parse_url($url, PHP_URL_HOST),
            'has_token_id' => is_string($tokenId) && $tokenId !== '',
            'expired_at' => $expiredAt?->toIso8601String(),
        ]);

        return [
            'payment_url' => $url,
            'token_id' => is_string($tokenId) && $tokenId !== '' ? $tokenId : null,
            'expired_at' => $expiredAt,
        ];
    }

    /**
     * Map internal method keys (kompatibel UI lama) ke kode DOKU checkout payment_method_types.
     */
    public static function checkoutMethodTypeForInternalMethod(string $method): ?string
    {
        return match ($method) {
            'va_bca' => 'VIRTUAL_ACCOUNT_BCA',
            'va_bni' => 'VIRTUAL_ACCOUNT_BNI',
            'va_bri' => 'VIRTUAL_ACCOUNT_BRI',
            'va_permata' => 'VIRTUAL_ACCOUNT_BANK_PERMATA',
            'va_mandiri_bill' => 'VIRTUAL_ACCOUNT_BANK_MANDIRI',
            'qris' => 'QRIS',
            'shopeepay' => 'EMONEY_SHOPEEPAY',
            'gopay' => config('services.doku.gopay_checkout_method', 'EMONEY_DANA'),
            default => null,
        };
    }

    private function parseExpiredDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || strlen($value) !== 14) {
            return null;
        }

        try {
            return Carbon::createFromFormat('YmdHis', $value, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }
}
