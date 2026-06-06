<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\MootaWebhookRecorded;
use App\Support\CustomerBookingBroadcast;
use App\Jobs\NotifyMuthowifOfPaidBooking;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Services\BookingPendingPaymentEnsurer;
use App\Support\PaymentFlowLog;
use Illuminate\Database\Eloquent\Builder;
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

        $mutations = $this->normalizedMutations($payload);
        PaymentFlowLog::info('moota.settle.payload_mutations', [
            'history_id' => $history->getKey(),
            'mutation_count' => count($mutations),
        ]);

        foreach ($mutations as $index => $mutation) {
            if (! is_array($mutation)) {
                continue;
            }

            $this->maybeSettleMutation($mutation, $history->getKey(), (int) $index, $payload);
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
     * Tagihan transfer bank Moota, atau placeholder (payment_type kosong) dari {@see BookingPendingPaymentEnsurer}.
     *
     * @param  Builder<BookingPayment>  $q
     * @return Builder<BookingPayment>
     */
    private function whereMootaBankTransferIncludingPlaceholder(Builder $q): Builder
    {
        return $q->where(function (Builder $w): void {
            $w->where('payment_type', 'like', 'bank_transfer_moota%')
                ->orWhereNull('payment_type')
                ->orWhere('payment_type', '');
        });
    }

    /**
     * @param  array<string, mixed>  $mutation
     * @param  array<string, mixed>  $envelopePayload  JSON webhook utuh (deskripsi BK-BYTG… kadang hanya di akar payload).
     */
    private function maybeSettleMutation(
        array $mutation,
        int|string|null $historyId = null,
        int $mutationIndex = 0,
        array $envelopePayload = [],
    ): void {
        $type = strtoupper((string) ($mutation['type'] ?? ''));
        if ($type !== 'CR') {
            PaymentFlowLog::info('moota.settle.skip_non_credit', [
                'history_id' => $historyId,
                'mutation_index' => $mutationIndex,
                'type' => $type !== '' ? $type : null,
            ]);

            return;
        }

        /** Mutasi dari Moota: array di akar JSON; payment_detail bisa kosong pada tes/manual. */
        $detail = is_array($mutation['payment_detail'] ?? null)
            ? $mutation['payment_detail']
            : [];

        $amountRaw = $mutation['amount'] ?? $detail['amount_captured'] ?? $detail['total'] ?? null;
        $incomingHint = is_numeric($amountRaw) ? (int) round((float) $amountRaw) : null;

        $orderId = $this->resolveBookingOrderId($detail, $mutation, $incomingHint, $envelopePayload);
        if ($orderId === null) {
            PaymentFlowLog::info('moota.settle.skip_unmapped_mutation', [
                'history_id' => $historyId,
                'mutation_index' => $mutationIndex,
                'mutation_id' => $mutation['mutation_id'] ?? $mutation['token'] ?? null,
                'bank_id' => $mutation['bank_id'] ?? null,
            ]);

            return;
        }

        PaymentFlowLog::info('moota.settle.order_resolved', [
            'history_id' => $historyId,
            'mutation_index' => $mutationIndex,
            'order_id' => $orderId,
            'has_payment_detail_order_id' => isset($detail['order_id']),
        ]);

        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        $mutationId = is_string($mutationId) ? $mutationId : null;

        $trxFromHook = $this->mootaTrxFromMutation($detail, $mutation);

        if (! is_numeric($amountRaw)) {
            PaymentFlowLog::info('moota.settle.skip_non_numeric_amount', [
                'history_id' => $historyId,
                'mutation_index' => $mutationIndex,
                'order_id' => $orderId,
            ]);

            return;
        }
        $incoming = (int) round((float) $amountRaw);

        $shouldNotify = false;
        $notifiedOrderId = $orderId;

        try {
            $shouldNotify = DB::transaction(function () use (
                $orderId,
                $incoming,
                $mutation,
                $detail,
                $mutationId,
                $trxFromHook,
                $historyId,
                $mutationIndex,
                &$notifiedOrderId,
                $envelopePayload,
            ): bool {
                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if ($payment === null) {
                    $trx = is_string($trxFromHook) ? trim($trxFromHook) : '';
                    if ($trx !== '') {
                        $payment = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                            ->where('gateway_transaction_id', $trx)
                            ->whereIn('status', ['pending', 'cancelled', 'settlement', 'capture'])
                            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'cancelled' THEN 1 WHEN 'settlement' THEN 2 WHEN 'capture' THEN 3 ELSE 4 END")
                            ->orderByDesc('id')
                            ->lockForUpdate()
                            ->first();
                    }

                    if ($payment !== null) {
                        PaymentFlowLog::info('moota.settle.payment_found_via_trx_fallback', [
                            'history_id' => $historyId,
                            'mutation_index' => $mutationIndex,
                            'order_id_webhook' => $orderId,
                            'order_id_db' => $payment->order_id,
                            'trx_id' => $trxFromHook,
                        ]);
                    }
                }

                if ($payment === null) {
                    $resolvedOid = $this->tryResolveOrderIdByBookingCode($detail, $mutation, $incoming, $envelopePayload);
                    if ($resolvedOid !== null) {
                        $payment = BookingPayment::query()
                            ->where('order_id', $resolvedOid)
                            ->lockForUpdate()
                            ->first();
                        if ($payment !== null) {
                            PaymentFlowLog::info('moota.settle.payment_found_via_booking_code_in_tx', [
                                'history_id' => $historyId,
                                'mutation_index' => $mutationIndex,
                                'order_id_db' => $payment->order_id,
                            ]);
                        }
                    }
                }

                if ($payment === null) {
                    PaymentFlowLog::warning('moota.settle.no_payment_row', [
                        'history_id' => $historyId,
                        'mutation_index' => $mutationIndex,
                        'order_id' => $orderId,
                        'trx_from_hook' => $trxFromHook,
                        'hint' => 'Tidak ada baris pembayaran (order_id / trx / kode BK-BYTG). Cek DB atau cocokkan booking_code + trx.',
                    ]);

                    return false;
                }

                $notifiedOrderId = (string) $payment->order_id;

                $paymentStatusBefore = (string) $payment->status;

                PaymentFlowLog::info('moota.settle.payment_row_loaded', [
                    'history_id' => $historyId,
                    'mutation_index' => $mutationIndex,
                    'payment_id' => $payment->getKey(),
                    'order_id' => $payment->order_id,
                    'order_id_webhook' => $orderId,
                    'payment_status' => $paymentStatusBefore,
                    'booking_id' => $payment->muthowif_booking_id,
                ]);

                $payType = is_string($payment->payment_type) ? trim($payment->payment_type) : '';
                /** Pembayaran sudah jelas driver lain (Doku/Midtrans); null = placeholder ensure / belum charge. */
                if ($payType !== '' && ! str_starts_with($payType, 'bank_transfer_moota')) {
                    PaymentFlowLog::info('moota.settle.skip_wrong_payment_type', [
                        'order_id' => $orderId,
                        'payment_type' => $payment->payment_type,
                    ]);

                    return false;
                }

                if ($payType === '') {
                    $payment->payment_type = 'bank_transfer_moota';
                }

                if ($payment->isSettled()) {
                    $bookingQuick = MuthowifBooking::query()
                        ->whereKey($payment->muthowif_booking_id)
                        ->lockForUpdate()
                        ->first();
                    if ($bookingQuick !== null && $bookingQuick->payment_status === PaymentStatus::Paid) {
                        PaymentFlowLog::info('moota.settle.skip_already_lunas', [
                            'order_id' => $payment->order_id,
                            'payment_status' => $payment->status,
                            'booking_payment_status' => $bookingQuick->payment_status->value,
                            'note' => 'Pembayaran sudah settlement/capture dan booking sudah Paid — tidak perlu proses ulang.',
                        ]);

                        return false;
                    }
                }

                $gatewayMeta = $payment->gateway_notification_payload ?? [];
                if (! is_array($gatewayMeta)) {
                    $gatewayMeta = [];
                }

                if ($mutationId !== null
                    && ($gatewayMeta['moota_last_processed_mutation_id'] ?? null) === $mutationId) {
                    PaymentFlowLog::info('moota.settle.skip_duplicate_mutation', [
                        'order_id' => $orderId,
                        'mutation_id' => $mutationId,
                    ]);

                    return false;
                }

                $detailTrust = $this->mootaPaymentDetailTrustsSuccessfulCharge($payment, $incoming, $detail, $trxFromHook);
                $expected = $this->expectedTransferAmount($payment, $detail);

                $structuredMeta = $this->webhookHasStructuredBgOrderOrTrxIds($detail, $mutation);
                $bkMatchesBlob = $this->bookingPaymentMatchesBkInFullBlob($payment, $detail, $mutation, $envelopePayload);
                $looseBkOnlyMatch = $bkMatchesBlob && ! $structuredMeta;

                if ($expected === null && ! $detailTrust && ! $looseBkOnlyMatch) {
                    PaymentFlowLog::warning('moota.settle.missing_expected_amount', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->getKey(),
                        'detail_trust' => false,
                    ]);

                    return false;
                }

                $amountOk = $detailTrust
                    || ($looseBkOnlyMatch && $this->amountOkWhenBkDescriptionTrusts($payment, $incoming, $expected, $detail, $trxFromHook))
                    || ($expected !== null && $this->incomingMatchesExpectedMootaAmount($incoming, $expected, $payment, $detail, $trxFromHook));

                if (! $amountOk) {
                    Log::critical('Moota webhook: nominal transfer tidak cocok', [
                        'order_id' => $orderId,
                        'expected' => $expected,
                        'incoming' => $incoming,
                        'detail_trust' => $detailTrust,
                    ]);
                    PaymentFlowLog::warning('moota.settle.amount_mismatch', [
                        'history_id' => $historyId,
                        'order_id' => $orderId,
                        'payment_id' => $payment->getKey(),
                        'payment_status' => $paymentStatusBefore,
                        'expected' => $expected,
                        'incoming' => $incoming,
                        'detail_trust' => $detailTrust,
                    ]);

                    return false;
                }

                $storedTrx = $payment->gateway_transaction_id;
                $trxMismatch = is_string($storedTrx) && $storedTrx !== ''
                    && is_string($trxFromHook) && $trxFromHook !== ''
                    && $storedTrx !== $trxFromHook;

                if ($trxMismatch && ! $detailTrust && ! $looseBkOnlyMatch) {
                    Log::warning('Moota webhook: trx_id tidak cocok', [
                        'order_id' => $orderId,
                        'stored' => $storedTrx,
                        'hook' => $trxFromHook,
                    ]);
                    PaymentFlowLog::warning('moota.settle.trx_mismatch', [
                        'history_id' => $historyId,
                        'order_id' => $orderId,
                        'payment_id' => $payment->getKey(),
                        'payment_status' => $paymentStatusBefore,
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

                    PaymentFlowLog::info('moota.settle.payment_settled_refund_state', [
                        'history_id' => $historyId,
                        'order_id' => $orderId,
                        'payment_id' => $payment->getKey(),
                        'from_status' => $paymentStatusBefore,
                        'to_status' => 'settlement',
                        'booking_payment_status' => $booking->payment_status->value,
                        'updated' => true,
                    ]);

                    return false;
                }

                if ($booking->payment_status === PaymentStatus::Paid) {
                    $payment->status = 'settlement';
                    $payment->settled_at = $payment->settled_at ?? now();
                    $payment->save();

                    PaymentFlowLog::info('moota.settle.payment_settled_booking_already_paid', [
                        'history_id' => $historyId,
                        'order_id' => $orderId,
                        'payment_id' => $payment->getKey(),
                        'from_status' => $paymentStatusBefore,
                        'to_status' => 'settlement',
                        'updated' => true,
                    ]);

                    return false;
                }

                if ($booking->status !== BookingStatus::Confirmed) {
                    if ($paymentStatusBefore === 'cancelled') {
                        $payment->status = 'settlement';
                        $payment->settled_at = now();
                        $payment->save();

                        PaymentFlowLog::info('moota.settle.cancelled_payment_settled_booking_unconfirmed', [
                            'history_id' => $historyId,
                            'order_id' => $orderId,
                            'payment_id' => $payment->getKey(),
                            'from_status' => 'cancelled',
                            'to_status' => 'settlement',
                            'booking_id' => $booking->getKey(),
                            'booking_status' => $booking->status->value,
                            'updated' => true,
                            'note' => 'Mutasi valid; booking belum Confirmed — baris pembayaran tetap di-settlement agar uang masuk tercatat.',
                        ]);
                    } else {
                        $payment->status = 'pending';
                        $payment->save();

                        PaymentFlowLog::info('moota.settle.booking_not_confirmed_payment_pending', [
                            'history_id' => $historyId,
                            'order_id' => $orderId,
                            'payment_id' => $payment->getKey(),
                            'from_status' => $paymentStatusBefore,
                            'booking_status' => $booking->status->value,
                            'updated' => $paymentStatusBefore !== 'pending',
                        ]);
                    }

                    return false;
                }

                $booking->payment_status = PaymentStatus::Paid;
                $booking->paid_at = now();
                $booking->save();

                $payment->status = 'settlement';
                $payment->settled_at = now();
                $payment->save();

                PaymentFlowLog::info('moota.settle.completed_booking_paid', [
                    'history_id' => $historyId,
                    'mutation_index' => $mutationIndex,
                    'order_id' => $orderId,
                    'payment_id' => $payment->getKey(),
                    'booking_id' => $booking->getKey(),
                    'from_payment_status' => $paymentStatusBefore,
                    'to_payment_status' => 'settlement',
                    'updated' => true,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Moota webhook: gagal menyelesaikan pembayaran', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
            PaymentFlowLog::warning('moota.settle.exception', [
                'order_id' => $orderId,
                'history_id' => $historyId,
                'mutation_index' => $mutationIndex,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if ($shouldNotify) {
            PaymentFlowLog::info('moota.settle.dispatch_notify', [
                'history_id' => $historyId,
                'order_id' => $notifiedOrderId,
            ]);
            $payment = BookingPayment::query()->where('order_id', $notifiedOrderId)->first();
            if ($payment !== null) {
                NotifyMuthowifOfPaidBooking::dispatchAfterResponse((string) $payment->muthowif_booking_id);
                CustomerBookingBroadcast::afterResponse((string) $payment->muthowif_booking_id);
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
    private function mootaPaymentDetailTrustsSuccessfulCharge(BookingPayment $payment, int $incoming, array $detail, ?string $trxFromHook = null): bool
    {
        if (strtoupper(trim((string) ($detail['status'] ?? ''))) !== 'SUCCESS') {
            return false;
        }

        $orderFromDetail = $this->trimmedOrderIdFromDetail($detail['order_id'] ?? null);
        $orderMatches = $orderFromDetail !== '' && $orderFromDetail === (string) $payment->order_id;

        $detailTrx = is_string($detail['trx_id'] ?? null) ? trim((string) $detail['trx_id']) : '';
        $storedTrx = is_string($payment->gateway_transaction_id) ? trim($payment->gateway_transaction_id) : '';
        $hookTrx = is_string($trxFromHook) ? trim($trxFromHook) : '';
        $trxLinksPayment = $detailTrx !== ''
            && $storedTrx !== ''
            && $detailTrx === $storedTrx
            && ($hookTrx === '' || $hookTrx === $detailTrx);

        if (! $orderMatches && ! $trxLinksPayment) {
            return false;
        }

        foreach (['total', 'amount_captured'] as $key) {
            $v = $detail[$key] ?? null;
            if (is_numeric($v) && (int) round((float) $v) === $incoming) {
                PaymentFlowLog::info('moota.settle.amount_ok_via_success_payment_detail', [
                    'order_id' => $payment->order_id,
                    'incoming' => $incoming,
                    'matched_by' => $orderMatches ? 'order_id' : 'trx_id',
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
    private function resolveBookingOrderId(
        array $detail,
        array $mutation,
        ?int $incomingAmount = null,
        array $envelopePayload = [],
    ): ?string {
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

        $fromBookingCode = $this->tryResolveOrderIdByBookingCode($detail, $mutation, $incomingAmount, $envelopePayload);
        if ($fromBookingCode !== null) {
            return $fromBookingCode;
        }

        $trx = $this->mootaTrxFromMutation($detail, $mutation);
        if ($trx !== null) {
            /** @var BookingPayment|null $payment */
            $payment = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                ->where('gateway_transaction_id', $trx)
                ->whereIn('status', ['pending', 'cancelled', 'settlement', 'capture'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'cancelled' THEN 1 WHEN 'settlement' THEN 2 WHEN 'capture' THEN 3 ELSE 4 END")
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

    /**
     * Kode booking (BK-BYTG…) ikut di label item / deskripsi mutasi Moota.
     *
     * @param  array<string, mixed>  $envelopePayload  JSON webhook utuh (agar `description` di akar ikut dipindai).
     * @return list<string>
     */
    private function extractBkBookingCodesFromMootaPayload(
        array $detail,
        array $mutation,
        array $envelopePayload = [],
    ): array {
        $parts = [$mutation, $detail];
        if ($envelopePayload !== []) {
            $parts[] = $envelopePayload;
        }
        $blob = json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        if (preg_match_all('/\b(BK-BYTG[0-9]+)\b/', $blob, $m)) {
            /** @var list<string> $codes */
            $codes = array_values(array_unique($m[1]));

            return $codes;
        }

        return [];
    }

    /**
     * Cocokkan mutasi ke booking lewat {@see MuthowifBooking::$booking_code}, lalu ke baris pembayaran Moota.
     *
     * @param  array<string, mixed>  $envelopePayload  JSON webhook utuh.
     */
    private function tryResolveOrderIdByBookingCode(
        array $detail,
        array $mutation,
        ?int $incomingAmount,
        array $envelopePayload = [],
    ): ?string {
        $trxHint = $this->mootaTrxFromMutation($detail, $mutation);

        foreach ($this->extractBkBookingCodesFromMootaPayload($detail, $mutation, $envelopePayload) as $code) {
            $byColumn = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                ->where('booking_code', $code)
                ->whereIn('status', ['pending', 'cancelled', 'settlement', 'capture'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'cancelled' THEN 1 WHEN 'settlement' THEN 2 WHEN 'capture' THEN 3 ELSE 4 END")
                ->orderByDesc('id')
                ->first();

            if ($byColumn !== null) {
                PaymentFlowLog::info('moota.settle.order_resolved_via_booking_payments.booking_code', [
                    'booking_code' => $code,
                    'order_id' => $byColumn->order_id,
                ]);

                return (string) $byColumn->order_id;
            }

            $booking = MuthowifBooking::query()->where('booking_code', $code)->first();
            if ($booking === null) {
                continue;
            }

            $bookingKey = $booking->getKey();

            if (is_string($trxHint) && trim($trxHint) !== '') {
                $p = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                    ->where('muthowif_booking_id', $bookingKey)
                    ->where('gateway_transaction_id', trim($trxHint))
                    ->orderByDesc('id')
                    ->first();
                if ($p !== null) {
                    PaymentFlowLog::info('moota.settle.order_resolved_via_booking_code_trx', [
                        'booking_code' => $code,
                        'order_id' => $p->order_id,
                    ]);

                    return (string) $p->order_id;
                }
            }

            if ($incomingAmount !== null && $incomingAmount > 0) {
                $candidates = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                    ->where('muthowif_booking_id', $bookingKey)
                    ->whereIn('status', ['pending', 'cancelled', 'settlement', 'capture'])
                    ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'cancelled' THEN 1 WHEN 'settlement' THEN 2 WHEN 'capture' THEN 3 ELSE 4 END")
                    ->orderByDesc('id')
                    ->get();
                foreach ($candidates as $payment) {
                    $exp = $this->expectedTransferAmount($payment, $detail);
                    if ($exp === null) {
                        continue;
                    }
                    if ($exp === $incomingAmount) {
                        PaymentFlowLog::info('moota.settle.order_resolved_via_booking_code_amount', [
                            'booking_code' => $code,
                            'order_id' => $payment->order_id,
                        ]);

                        return (string) $payment->order_id;
                    }
                    if ($this->incomingMatchesExpectedMootaAmount($incomingAmount, $exp, $payment, $detail, $trxHint)) {
                        PaymentFlowLog::info('moota.settle.order_resolved_via_booking_code_amount_detail', [
                            'booking_code' => $code,
                            'order_id' => $payment->order_id,
                        ]);

                        return (string) $payment->order_id;
                    }
                }
            }

            $fallback = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
                ->where('muthowif_booking_id', $bookingKey)
                ->whereIn('status', ['pending', 'cancelled', 'settlement', 'capture'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'cancelled' THEN 1 WHEN 'settlement' THEN 2 WHEN 'capture' THEN 3 ELSE 4 END")
                ->orderByDesc('id')
                ->first();

            if ($fallback !== null) {
                PaymentFlowLog::info('moota.settle.order_resolved_via_booking_code_fallback', [
                    'booking_code' => $code,
                    'order_id' => $fallback->order_id,
                    'payment_status' => $fallback->status,
                ]);

                return (string) $fallback->order_id;
            }
        }

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

        $candidates = $this->whereMootaBankTransferIncludingPlaceholder(BookingPayment::query())
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
     * Ada order_id BG-… atau trx_id di field terstruktur payment_detail / mutasi (bukan hanya di dalam teks description).
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     */
    private function webhookHasStructuredBgOrderOrTrxIds(array $detail, array $mutation): bool
    {
        foreach ([$detail['order_id'] ?? null, $mutation['order_id'] ?? null] as $raw) {
            $oid = $this->trimmedOrderIdFromDetail($raw);
            if ($oid !== '' && str_starts_with($oid, 'BG-')) {
                return true;
            }
        }

        foreach ([$detail['trx_id'] ?? null, $mutation['trx_id'] ?? null] as $t) {
            if (is_string($t) && trim($t) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $mutation
     * @param  array<string, mixed>  $envelopePayload
     */
    private function bookingPaymentMatchesBkInFullBlob(
        BookingPayment $payment,
        array $detail,
        array $mutation,
        array $envelopePayload,
    ): bool {
        $codes = $this->extractBkBookingCodesFromMootaPayload($detail, $mutation, $envelopePayload);
        if ($codes === []) {
            return false;
        }

        $pc = is_string($payment->booking_code) ? trim($payment->booking_code) : '';
        if ($pc !== '' && in_array($pc, $codes, true)) {
            return true;
        }

        $booking = $payment->muthowifBooking;
        if ($booking === null) {
            $payment->loadMissing('muthowifBooking');
            $booking = $payment->muthowifBooking;
        }
        $bc = is_string($booking?->booking_code) ? trim((string) $booking->booking_code) : '';

        return $bc !== '' && in_array($bc, $codes, true);
    }

    /**
     * Nominal saat kepercayaan lewat deskripsi BK-BYTG saja (tanpa order/trx terstruktur): pakai metadata atau gross_amount baris.
     */
    private function amountOkWhenBkDescriptionTrusts(
        BookingPayment $payment,
        int $incoming,
        ?int $expected,
        array $detail,
        ?string $trxFromHook,
    ): bool {
        if ($expected !== null) {
            return $this->incomingMatchesExpectedMootaAmount($incoming, $expected, $payment, $detail, $trxFromHook);
        }

        return $incoming === (int) $payment->gross_amount;
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
