<?php

namespace App\Payments\Contracts;

use App\Models\BookingPayment;
use App\Payments\DTO\SnapPaymentSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface SnapPaymentProviderInterface
{
    public function isConfigured(): bool;

    /**
     * Buat session pembayaran untuk booking tertentu.
     *
     * @throws \RuntimeException
     */
    public function createPaymentSession(BookingPayment $payment, ?string $method = null): SnapPaymentSession;

    /**
     * Proses notification/webhook dari provider.
     * Implementasi wajib melakukan validasi signature dan melakukan settlement bila valid.
     */
    public function handleNotification(Request $request): Response;
}

