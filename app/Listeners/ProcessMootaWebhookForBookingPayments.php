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

        /** @var array|null $payload */
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
     * @param  array<string, mixed>  $payload  Bisa objek mutasi tunggal atau daftar di akar JSON.
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

        /** Mutasi dari Moota: array di akar JSON; payment_detail bisa kosong pada tes/manual. */
        $detail = is_array($mutation['payment_detail'] ?? null)
            ? $mutation['payment_detail']
            : [];

        $amountRaw = $mutation['amount'] ?? $detail['amount_captured'] ?? $detail['total'] ?? null;
        $incomingHint = is_numeric($amountRaw) ? (int) round((float) $amountRaw) : null;

        $orderId = $this->resolveBookingOrderId($detail, $mutation, $incomingHint);
        if ($orderId === null) {
            PaymentFlowLog::info('moota.settle.skip_unmapped_mutation', [
                'mutation_id' => $mutation['mutation_id'] ?? $mutation['token'] ?? null,
                'bank_id' => $mutation['bank_id'] ?? null,
            ]);

            return;
        }

        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        $mutationId = is_string($mutationId) ? $mutationId : null;

        $trxFromHook = $this->mootaTrxFromMutation($detail, $mutation);

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

                $payType = is_string($payment->payment_type) ? $payment->payment_type : '';
                if ($payType === '' || ! str_starts_with($payType, 'bank_transfer_moota')) {
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

                $detailTrust = $this->mootaPaymentDetailTrustsSuccessfulCharge($payment, $incoming, $detail);
                $expected = $this->expectedTransferAmount($payment, $detail);

                if ($expected === null && ! $detailTrust) {
                    PaymentFlowLog::warning('moota.settle.missing_expected_amount', [
                        'order_id' => $orderId,
                    ]);

                    return false;
                }

                $amountOk = $detailTrust
                    || ($expected !== null && $this->incomingMatchesExpectedMootaAmount($incoming, $expected, $payment, $detail, $trxFromHook));

                if (! $amountOk) {
                    Log::critical('Moota webhook: nominal transfer tidak cocok', [
                        'order_id' => $orderId,
                        'expected' => $expected,
                        'incoming' => $incoming,
                        'detail_trust' => $detailTrust,
                    ]);
                    PaymentFlowLog::warning('moota.settle.amount_mismatch', [
                        'order_id' => $orderId,
                        'expected' => $expected,
                        'incoming' => $incoming,
                    ]);

                    return false;
                }

                $storedTrx = $payment->gateway_transaction_id;
                $trxMismatch = is_string($storedTrx) && $storedTrx !== ''
                    && is_string($trxFromHook) && $trxFromHook !== ''
                    && $storedTrx !== $trxFromHook;

                if ($trxMismatch && ! $detailTrust) {
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
     * Nominal transfer dari respons create-transaction (isi saat charge) atau dari payment_detail webhook.
     */
    private function expectedTransferAmount(BookingPayment $payment, array $detail): ?int
    {
        $payload = $payment->gateway_notification_payload ?? [];
        if (! is_array($payload)) {
            return null;
        }

        $explicit = $payload['moota_expected_transfer_total'] ?? null;
        if (is_numeric($explicit)) {
            return (int) round((float) $explicit);
        }

        $root = $payload['moota_create_transaction_response'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $data = $root['data'] ?? null;
        if (! is_array($data)) {
            $data = $root;
        }

        foreach (['total', 'amount', 'grand_total'] as $key) {
            $v = data_get($data, $key);
            if (is_numeric($v)) {
                return (int) round((float) $v);
            }
        }

        $fromDetail = $detail['total'] ?? $detail['amount_captured'] ?? null;
        if (is_numeric($fromDetail)) {
            return (int) round((float) $fromDetail);
        }

        return null;
    }

    /**
     * Moota mengisi payment_detail lengkap (order_id, status SUCCESS, total) — cukup untuk verifikasi
     * bila metadata create-transaction di DB tidak ada atau tidak selaras kode unik.
     */
    private function mootaPaymentDetailTrustsSuccessfulCharge(BookingPayment $payment, int $incoming, array $detail): bool
    {
        if (strtoupper(trim((string) ($detail['status'] ?? ''))) !== 'SUCCESS') {
            return false;
        }

        $orderFromDetail = $this->trimmedOrderIdFromDetail($detail['order_id'] ?? null);
        if ($orderFromDetail === '' || $orderFromDetail !== (string) $payment->order_id) {
            return false;
        }

        foreach (['total', 'amount_captured'] as $key) {
            $v = $detail[$key] ?? null;
            if (is_numeric($v) && (int) round((float) $v) === $incoming) {
                PaymentFlowLog::info('moota.settle.amount_ok_via_success_payment_detail', [
                    'order_id' => $payment->order_id,
                    'incoming' => $incoming,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Mutasi bank memakai total final (termasuk kode unik Moota); metadata lama kadang hanya punya nominal dasar.
     * Bila trx_id sama dan payment_detail dari webhook menyatakan nominal = mutasi, anggap sah.
     */
    private function incomingMatchesExpectedMootaAmount(int $incoming, int $expected, BookingPayment $payment, array $detail, ?string $trxFromHook): bool
    {
        if ($incoming === $expected) {
            return true;
        }

        if ($this->webhookPaymentDetailConfirmsBankAmount($payment, $incoming, $detail, $trxFromHook)) {
            PaymentFlowLog::info('moota.settle.amount_ok_via_payment_detail', [
                'order_id' => $payment->order_id,
                'incoming' => $incoming,
                'metadata_expected' => $expected,
            ]);

            return true;
        }

        return false;
    }

    private function webhookPaymentDetailConfirmsBankAmount(BookingPayment $payment, int $incoming, array $detail, ?string $trxFromHook): bool
    {
        $hookTrx = is_string($trxFromHook) ? trim($trxFromHook) : '';
        $storedTrx = is_string($payment->gateway_transaction_id) ? trim($payment->gateway_transaction_id) : '';
        if ($hookTrx === '' || $storedTrx === '' || $hookTrx !== $storedTrx) {
            return false;
        }

        foreach (['total', 'amount_captured'] as $key) {
            $v = $detail[$key] ?? null;
            if (is_numeric($v) && (int) round((float) $v) === $incoming) {
                return true;
            }
        }

        return false;
    }

    /**
     * Order id dari payload (BG-...), trx_id / teks (PYM-/BG-), atau pasangan bank_id + nominal transfer.
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     */
    private function resolveBookingOrderId(array $detail, array $mutation, ?int $incomingAmount = null): ?string
    {
        $fromPayload = $this->trimmedOrderIdFromDetail($detail['order_id'] ?? null);
        if ($fromPayload !== '' && str_starts_with($fromPayload, 'BG-')) {
            PaymentFlowLog::info('moota.settle.order_resolved_via_payment_detail', ['order_id' => $fromPayload]);

            return $fromPayload;
        }

        $fromMutationTop = $this->trimmedOrderIdFromDetail($mutation['order_id'] ?? null);
        if ($fromMutationTop !== '' && str_starts_with($fromMutationTop, 'BG-')) {
            PaymentFlowLog::info('moota.settle.order_resolved_via_mutation_root', ['order_id' => $fromMutationTop]);

            return $fromMutationTop;
        }

        $fromText = $this->mootaExtractOrderIdBg($detail, $mutation);
        if ($fromText !== null) {
            return $fromText;
        }

        $trx = $this->mootaTrxFromMutation($detail, $mutation);
        if ($trx !== null) {
            /** @var BookingPayment|null $payment */
            $payment = BookingPayment::query()
                ->where('payment_type', 'like', 'bank_transfer_moota%')
                ->where('gateway_transaction_id', $trx)
                ->whereIn('status', ['pending', 'cancelled'])
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->first();

            if ($payment === null) {
                PaymentFlowLog::info('moota.settle.skip_trx_unknown', ['trx_id' => $trx]);
            } else {
                PaymentFlowLog::info('moota.settle.order_resolved_via_trx', [
                    'trx_id' => $trx,
                    'order_id' => $payment->order_id,
                ]);

                return $payment->order_id;
            }
        }

        if ($incomingAmount !== null && $incomingAmount > 0) {
            $fromBank = $this->tryResolveOrderIdByBankAndAmount($mutation, $incomingAmount);
            if ($fromBank !== null) {
                return $fromBank;
            }
        }

        PaymentFlowLog::info('moota.settle.skip_no_order_or_trx', [
            'raw_order_id' => $detail['order_id'] ?? null,
        ]);

        return null;
    }

    private function trimmedOrderIdFromDetail(mixed $rawOrder): string
    {
        if (is_string($rawOrder)) {
            return trim($rawOrder);
        }

        if (is_scalar($rawOrder) && $rawOrder !== null) {
            return trim((string) $rawOrder);
        }

        return '';
    }

    /**
     * Cocokkan mutasi ke pembayaran pending bila payment_detail kosong: bank_id Moota + nominal sama dengan total create-transaction.
     */
    private function tryResolveOrderIdByBankAndAmount(array $mutation, int $incoming): ?string
    {
        $mBankId = $this->mutationMootaBankId($mutation);
        if ($mBankId === '') {
            return null;
        }

        $candidates = BookingPayment::query()
            ->where('payment_type', 'like', 'bank_transfer_moota%')
            ->whereIn('status', ['pending', 'cancelled'])
            ->whereHas('muthowifBooking', function ($q): void {
                $q->where('status', BookingStatus::Confirmed)
                    ->where('payment_status', PaymentStatus::Pending);
            })
            ->get();

        $byBank = [];
        foreach ($candidates as $payment) {
            $expected = $this->expectedTransferAmount($payment, []);
            if ($expected === null || $expected !== $incoming) {
                continue;
            }
            $pBank = $this->mootaBankAccountIdFromCreatePayload($payment);
            if ($pBank !== null && $pBank === $mBankId) {
                $byBank[] = (string) $payment->order_id;
            }
        }

        if (count($byBank) === 1) {
            PaymentFlowLog::info('moota.settle.order_resolved_via_bank_amount', [
                'bank_id' => $mBankId,
                'amount' => $incoming,
            ]);

            return $byBank[0];
        }

        if (count($byBank) > 1) {
            Log::warning('moota.settle.ambiguous_bank_amount', [
                'bank_id' => $mBankId,
                'amount' => $incoming,
            ]);

            return null;
        }

        $amountOnly = $candidates->filter(function (BookingPayment $payment) use ($incoming): bool {
            $expected = $this->expectedTransferAmount($payment, []);

            return $expected !== null && $expected === $incoming;
        });

        if ($amountOnly->count() === 1) {
            $first = $amountOnly->first();
            PaymentFlowLog::info('moota.settle.order_resolved_via_amount_only', [
                'order_id' => $first?->order_id,
            ]);

            return $first !== null ? (string) $first->order_id : null;
        }

        return null;
    }

    private function mutationMootaBankId(array $mutation): string
    {
        foreach ([
            $mutation['bank_id'] ?? null,
            data_get($mutation, 'bank.bank_id'),
            data_get($mutation, 'bank.token'),
            data_get($mutation, 'account.account_id'),
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function mootaBankAccountIdFromCreatePayload(BookingPayment $payment): ?string
    {
        $gatewayMeta = $payment->gateway_notification_payload ?? [];
        if (! is_array($gatewayMeta)) {
            return null;
        }

        $chosen = $gatewayMeta['moota_chosen_bank_account_id'] ?? null;
        if (is_string($chosen) && trim($chosen) !== '') {
            return trim($chosen);
        }

        $root = $gatewayMeta['moota_create_transaction_response'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $data = $root['data'] ?? null;
        if (! is_array($data)) {
            $data = $root;
        }

        foreach (['bank_account_id', 'bank_id', 'token'] as $key) {
            $v = data_get($data, $key);
            if (is_string($v) && trim($v) !== '') {
                return trim($v);
            }
        }

        return null;
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
