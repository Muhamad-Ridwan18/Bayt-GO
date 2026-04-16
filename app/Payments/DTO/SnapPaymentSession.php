<?php

namespace App\Payments\DTO;

final class SnapPaymentSession
{
    public function __construct(
        public readonly ?string $snapToken,
        public readonly ?string $clientKey,
        public readonly ?string $snapJsUrl,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $providerReferenceId = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $instructions = null,
    ) {}
}

