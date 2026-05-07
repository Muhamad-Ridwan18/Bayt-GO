<?php

namespace App\Payments\Moota;

use App\Models\BookingPayment;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\DTO\SnapPaymentSession;
use App\Services\Moota\MootaBookingChargeService;
use App\Support\PaymentFlowLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class MootaSnapPaymentProvider implements SnapPaymentProviderInterface
{
    public function __construct(
        private readonly MootaBookingChargeService $moota,
    ) {}

    public function isConfigured(): bool
    {
        return $this->moota->isConfigured();
    }

    public function createPaymentSession(BookingPayment $payment, ?string $method = null, ?string $mootaBankAccountId = null): SnapPaymentSession
    {
        if ($method !== 'bank_transfer_moota') {
            throw new RuntimeException(__('bookings.flash.method_not_supported'));
        }

        PaymentFlowLog::info('moota.provider.create_session', [
            'booking_id' => $payment->muthowif_booking_id,
            'order_id' => $payment->order_id,
            'gross_amount' => $payment->gross_amount,
        ]);

        try {
            $explicit = is_string($mootaBankAccountId) ? trim($mootaBankAccountId) : '';
            $result = $this->moota->createChargeForBookingPayment(
                $payment,
                $explicit !== '' ? $explicit : null,
            );
        } catch (RuntimeException $e) {
            PaymentFlowLog::warning('moota.provider.exception', ['message' => $e->getMessage()]);

            throw $e;
        }

        $mergedPayload = [
            'moota_create_transaction_response' => $result['payload'],
            'moota_chosen_bank_account_id' => $result['bank_account_id'],
        ];

        $payment->update([
            'gateway_transaction_id' => $result['trx_id'],
            'gateway_notification_payload' => $mergedPayload,
            'payment_type' => 'bank_transfer_moota',
        ]);

        $instructions = array_filter([
            'checkout_url' => $result['payment_url'],
            'expiry_time' => $result['expiry_time'],
            'moota_expected_transfer_total' => $result['moota_total'],
        ], static fn ($v) => $v !== null && $v !== '');

        PaymentFlowLog::info('moota.provider.session_ok', [
            'order_id' => $payment->order_id,
            'trx_id' => $result['trx_id'],
            'transfer_total_hint' => $result['moota_total'],
        ]);

        return new SnapPaymentSession(
            snapToken: null,
            clientKey: null,
            snapJsUrl: null,
            paymentUrl: $result['payment_url'],
            providerReferenceId: $result['trx_id'],
            instructions: $instructions,
        );
    }

    /**
     * Notifikasi pembayaran Moota lewat POST /webhooks/moota, bukan lewat jalur SNAP provider.
     */
    public function handleNotification(Request $request): Response
    {
        Log::notice('Notifikasi Moota SNAP tidak digunakan; webhook di /webhooks/moota.');

        return response()->json([
            'message' => __('bookings.payment.moota_notification_wrong_route'),
        ], Response::HTTP_NOT_FOUND);
    }
}
