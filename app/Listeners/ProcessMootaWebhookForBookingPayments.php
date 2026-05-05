<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\CustomerBookingUpdated;
use App\Events\MootaWebhookRecorded;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Support\PaymentFlowLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessMootaWebhookForBookingPayments
{
    public function handle(MootaWebhookRecorded $event): void
    {
        $history = $event->history;
        $secret = (string) config('services.moota.signing_secret', '');
        if ($secret !== '' && $history->signature_verified !== true) {
            PaymentFlowLog::info('moota.settle.skip_signature', ['id' => $history->id]);

            return;
        }

        if ($history->parse_error !== null) {
            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $history->payload;
        if (! is_array($payload)) {
            return;
        }

        foreach ($this->normalizedMutations($payload) as $mutation) {
            $this->maybeSettleMutation($mutation);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizedMutations(array $payload): array
    {
        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $payload */
            return $payload;
        }

        foreach (['mutations', 'data', 'items'] as $key) {
            $v = $payload[$key] ?? null;
            if (is_array($v) && array_is_list($v)) {
                /** @var list<array<string, mixed>> $v */
                return $v;
            }
        }

        return [$payload];
    }

    /**
     * @param  array<string, mixed>  $mutation
     */
    private function maybeSettleMutation(array $mutation): void
    {
        $type = strtoupper((string) ($mutation['type'] ?? ''));
        if ($type !== 'CR') {
            return;
        }

        $detail = $mutation['payment_detail'] ?? null;
        if (! is_array($detail)) {
            return;
        }

        $orderId = $detail['order_id'] ?? null;
        if (! is_string($orderId) || $orderId === '' || ! str_starts_with($orderId, 'BG-')) {
            return;
        }

        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        $mutationId = is_string($mutationId) ? $mutationId : null;

        $trxFromHook = $detail['trx_id'] ?? null;
        $trxFromHook = is_string($trxFromHook) ? $trxFromHook : null;

        $amountRaw = $mutation['amount'] ?? $detail['amount_captured'] ?? $detail['total'] ?? null;
        if (! is_numeric($amountRaw)) {
            return;
        }
        $incoming = (int) round((float) $amountRaw);

        $shouldNotify = false;

        try {
            $shouldNotify = DB::transaction(function () use ($orderId, $incoming, $mutation, $detail, $mutationId, $trxFromHook): bool {
                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if ($payment === null) {
                    return false;
                }

                if ($payment->payment_type !== 'bank_transfer_moota') {
                    return false;
                }

                if ($payment->isSettled()) {
                    return false;
                }

                $gatewayMeta = $payment->gateway_notification_payload ?? [];
                if (! is_array($gatewayMeta)) {
                    $gatewayMeta = [];
                }

                if ($mutationId !== null
                    && ($gatewayMeta['moota_last_processed_mutation_id'] ?? null) === $mutationId) {
                    return false;
                }

                $expected = $this->expectedTransferAmount($payment, $detail);
                if ($expected === null) {
                    PaymentFlowLog::warning('moota.settle.missing_expected_amount', [
                        'order_id' => $orderId,
                    ]);

                    return false;
                }

                if ($incoming !== $expected) {
                    Log::critical('Moota webhook: nominal transfer tidak cocok', [
                        'order_id' => $orderId,
                        'expected' => $expected,
                        'incoming' => $incoming,
                    ]);
                    PaymentFlowLog::warning('moota.settle.amount_mismatch', [
                        'order_id' => $orderId,
                        'expected' => $expected,
                        'incoming' => $incoming,
                    ]);

                    return false;
                }

                $storedTrx = $payment->gateway_transaction_id;
                if (is_string($storedTrx) && $storedTrx !== ''
                    && is_string($trxFromHook) && $trxFromHook !== ''
                    && $storedTrx !== $trxFromHook) {
                    Log::warning('Moota webhook: trx_id tidak cocok', [
                        'order_id' => $orderId,
                        'stored' => $storedTrx,
                        'hook' => $trxFromHook,
                    ]);
                    PaymentFlowLog::warning('moota.settle.trx_mismatch', [
                        'order_id' => $orderId,
                    ]);

                    return false;
                }

                /** @var MuthowifBooking $booking */
                $booking = MuthowifBooking::query()
                    ->whereKey($payment->muthowif_booking_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $merged = $gatewayMeta;
                $merged['moota_webhook_mutation'] = $mutation;
                $merged['moota_last_processed_mutation_id'] = $mutationId;
                $payment->gateway_notification_payload = $merged;

                if (in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true)) {
                    $payment->status = 'settlement';
                    $payment->save();

                    return false;
                }

                if ($booking->payment_status === PaymentStatus::Paid) {
                    $payment->status = 'settlement';
                    $payment->settled_at = $payment->settled_at ?? now();
                    $payment->save();

                    return false;
                }

                if ($booking->status !== BookingStatus::Confirmed) {
                    $payment->status = 'pending';
                    $payment->save();

                    return false;
                }

                $booking->payment_status = PaymentStatus::Paid;
                $booking->paid_at = now();
                $booking->save();

                $payment->status = 'settlement';
                $payment->settled_at = now();
                $payment->save();

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Moota webhook: gagal menyelesaikan pembayaran', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
            PaymentFlowLog::warning('moota.settle.exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if ($shouldNotify) {
            PaymentFlowLog::info('moota.settle.dispatch_notify', ['order_id' => $orderId]);
            $payment = BookingPayment::query()->where('order_id', $orderId)->first();
            if ($payment !== null) {
                NotifyMuthowifOfPaidBooking::dispatchAfterResponse((string) $payment->muthowif_booking_id);
                $booking = MuthowifBooking::query()->find($payment->muthowif_booking_id);
                if ($booking !== null) {
                    broadcast(new CustomerBookingUpdated($booking->fresh()));
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function expectedTransferAmount(BookingPayment $payment, array $detail): ?int
    {
        $payload = $payment->gateway_notification_payload ?? [];
        if (! is_array($payload)) {
            return null;
        }

        $root = $payload['moota_create_transaction_response'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $data = $root['data'] ?? null;
        if (! is_array($data)) {
            $data = $root;
        }

        $total = data_get($data, 'total');
        if (is_numeric($total)) {
            return (int) round((float) $total);
        }

        $fromDetail = $detail['total'] ?? $detail['amount_captured'] ?? null;
        if (is_numeric($fromDetail)) {
            return (int) round((float) $fromDetail);
        }

        return null;
    }
}
