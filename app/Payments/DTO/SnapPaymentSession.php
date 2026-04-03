<?php

namespace App\Payments\DTO;

final class SnapPaymentSession
{
    public function __construct(
        // Untuk Midtrans Snap (legacy). Untuk Xendit, field ini bisa null.
        public readonly ?string $snapToken,
        public readonly ?string $clientKey,
        public readonly ?string $snapJsUrl,
        // Untuk Xendit payment/invoice.
        public readonly ?string $paymentUrl = null,
        public readonly ?string $providerReferenceId = null,
    ) {}
}

