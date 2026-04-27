<?php

namespace App\Services\Doku;

use App\Models\BookingPayment;
use App\Support\PaymentFlowLog;
use RuntimeException;

/**
 * Mobile / API: VA lewat Jokul direct payment-code; QRIS & e-wallet lewat DOKU Checkout (hosted).
 */
final class DokuDirectChargeService
{
    public function __construct(
        private readonly DokuCheckoutService $checkout,
        private readonly DokuVirtualAccountService $virtualAccount,
    ) {}

    public function isConfigured(): bool
    {
        return $this->checkout->isConfigured();
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
     */
    public function createChargeSession(BookingPayment $payment, string $method): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('DOKU belum dikonfigurasi.');
        }

        if (str_starts_with($method, 'va_')) {
            PaymentFlowLog::info('doku.direct.branch', [
                'invoice_number' => $payment->order_id,
                'branch' => 'virtual_account',
                'method' => $method,
            ]);
            $va = $this->virtualAccount->createPaymentCode($payment, $method);

            return [
                'transaction_id' => $va['transaction_id'],
                'payment_type' => $method,
                'va_bank' => $va['va_bank'],
                'va_number' => $va['va_number'],
                'bill_key' => $va['bill_key'],
                'biller_code' => $va['biller_code'],
                'qr_string' => null,
                'deeplink_url' => null,
                'checkout_url' => null,
                'expiry_time' => $va['expiry_time'],
            ];
        }

        $dokuMethod = DokuCheckoutService::checkoutMethodTypeForInternalMethod($method);
        if ($dokuMethod === null) {
            throw new RuntimeException('Metode pembayaran tidak didukung.');
        }

        PaymentFlowLog::info('doku.direct.branch', [
            'invoice_number' => $payment->order_id,
            'branch' => 'checkout',
            'method' => $method,
            'doku_method_type' => $dokuMethod,
        ]);

        $created = $this->checkout->createCheckout($payment, [$dokuMethod], null);

        return [
            'transaction_id' => $created['token_id'],
            'payment_type' => $method,
            'va_bank' => null,
            'va_number' => null,
            'bill_key' => null,
            'biller_code' => null,
            'qr_string' => null,
            'deeplink_url' => null,
            'checkout_url' => $created['payment_url'],
            'expiry_time' => $created['expired_at']?->toIso8601String(),
        ];
    }
}
