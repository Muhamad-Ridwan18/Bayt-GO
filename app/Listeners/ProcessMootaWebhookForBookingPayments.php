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
use Illuminate\Support\Str;

final class ProcessMootaWebhookForBookingPayments
{
    public function handle(MootaWebhookRecorded $event): void
    {
        $history = $event->history;
        Log::info('moota.settle.listener_start', ['history_id' => $history->getKey()]);

        $secret = (string) config('services.moota.signing_secret', '');
        if ($secret !== '' && $history->signature_verified !== true) {
            Log::warning('moota.settle.skipped_signature', [
                'history_id' => $history->getKey(),
                'signature_verified' => $history->signature_verified,
            ]);
            PaymentFlowLog::info('moota.settle.skip_signature', ['id' => $history->id]);

            return;
        }

        if ($history->parse_error !== null) {
            Log::warning('moota.settle.skipped_bad_json', [
                'history_id' => $history->getKey(),
                'parse_error' => Str::limit((string) $history->parse_error, 512),
            ]);

            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $history->payload;
        if (! is_array($payload)) {
            Log::warning('moota.settle.skipped_payload_missing', ['history_id' => $history->getKey()]);

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

        /** Mutasi “scrapping” sering tidak punya payment_detail; payment hosted bisa mengisinya. */
        $detail = is_array($mutation['payment_detail'] ?? null)
            ? $mutation['payment_detail']
            : [];

        $orderId = $this->resolveBookingOrderId($detail, $mutation);
        if ($orderId === null) {
            return;
        }

        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        $mutationId = is_string($mutationId) ? $mutationId : null;

        $trxFromHook = $this->mootaTrxFromMutation($detail, $mutation);

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

    /**
     * Order id dari payload (BG-...) atau, jika kosong seperti banyak webhook mutasi Moota,
     * lewat trx_id (PYM-...) yang sama dengan gateway_transaction_id booking kita.
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     */
    private function resolveBookingOrderId(array $detail, array $mutation): ?string
    {
        $rawOrder = $detail['order_id'] ?? null;
        $fromPayload = is_string($rawOrder) ? trim($rawOrder) : '';
        if ($fromPayload !== '' && str_starts_with($fromPayload, 'BG-')) {
            return $fromPayload;
        }

        $fromText = $this->mootaExtractOrderIdBg($detail, $mutation);
        if ($fromText !== null) {
            return $fromText;
        }

        $trx = $this->mootaTrxFromMutation($detail, $mutation);
        if ($trx === null) {
            PaymentFlowLog::info('moota.settle.skip_no_order_or_trx', [
                'raw_order_id' => is_string($rawOrder) ? $rawOrder : null,
            ]);

            return null;
        }

        /** @var BookingPayment|null $payment */
        $payment = BookingPayment::query()
            ->where('payment_type', 'bank_transfer_moota')
            ->where('gateway_transaction_id', $trx)
            ->where('status', 'pending')
            ->first();

        if ($payment === null) {
            PaymentFlowLog::info('moota.settle.skip_trx_unknown', ['trx_id' => $trx]);

            return null;
        }

        PaymentFlowLog::info('moota.settle.order_resolved_via_trx', [
            'trx_id' => $trx,
            'order_id' => $payment->order_id,
        ]);

        return $payment->order_id;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     */
    private function mootaTrxFromMutation(array $detail, array $mutation): ?string
    {
        foreach ([$detail['trx_id'] ?? null, $mutation['trx_id'] ?? null] as $candidate) {
            if (is_string($candidate) && ($t = trim($candidate)) !== '') {
                return $t;
            }
        }

        $haystacks = [];
        foreach (['note', 'description', 'unique_note'] as $k) {
            foreach ([$detail[$k] ?? null, $mutation[$k] ?? null] as $text) {
                if (is_string($text) && $text !== '') {
                    $haystacks[] = $text;
                }
            }
        }

        $haystacks[] = json_encode($mutation, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE) ?: '';

        foreach ($haystacks as $haystack) {
            if (! is_string($haystack) || $haystack === '') {
                continue;
            }

            if (preg_match('/\b(PYM-[A-Za-z0-9\-]+)\b/', $haystack, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Kadang order_id ikut di catatan transfer / deskripsi mutasi (termasuk JSON bertingkat).
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     */
    private function mootaExtractOrderIdBg(array $detail, array $mutation): ?string
    {
        $haystacks = [];
        foreach (['note', 'description', 'order_id'] as $k) {
            foreach ([$detail[$k] ?? null, $mutation[$k] ?? null] as $text) {
                if (is_string($text) && $text !== '') {
                    $haystacks[] = $text;
                }
            }
        }

        $haystacks[] = json_encode($mutation, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE) ?: '';

        foreach ($haystacks as $haystack) {
            if (! is_string($haystack) || $haystack === '') {
                continue;
            }

            if (preg_match('/\b(BG-[A-Za-z0-9\-]+)\b/', $haystack, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
