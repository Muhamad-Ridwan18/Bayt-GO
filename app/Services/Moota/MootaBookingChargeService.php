<?php

namespace App\Services\Moota;

use App\Models\BookingPayment;
use App\Support\PaymentFlowLog;
use Illuminate\Support\Facades\Route;
use RuntimeException;

final class MootaBookingChargeService
{
    public function __construct(
        private readonly MootaApiClient $client,
        private readonly \App\Services\CurrencyService $currencyService,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array{
     *   payment_url:string,
     *   trx_id:?string,
     *   expiry_time:?string,
     *   moota_total:?int,
     *   payload:array<string,mixed>,
     *   normalized_data:array<string,mixed>,
     *   bank_account_id:string
     * }
     */
    public function createChargeForBookingPayment(BookingPayment $payment, ?string $explicitMootaBankAccountId = null): array
    {
        if (! $this->client->isConfigured()) {
            throw new RuntimeException('Moota belum dikonfigurasi.');
        }

        $payment->loadMissing(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile']);

        $booking = $payment->muthowifBooking;
        if ($booking === null) {
            throw new RuntimeException('Booking tidak ditemukan.');
        }

        $customer = $booking->customer;
        $name = trim((string) ($customer?->name ?? 'Pelanggan'));
        if ($name === '') {
            $name = 'Pelanggan';
        }

        $phoneRaw = ($customer !== null && filled($customer->phone))
            ? (string) preg_replace('/\D+/', '', (string) $customer->phone)
            : '';
        $phone = null;
        if ($phoneRaw !== '' && str_starts_with($phoneRaw, '0')) {
            $phone = '+62'.substr($phoneRaw, 1);
        } elseif ($phoneRaw !== '' && str_starts_with($phoneRaw, '62')) {
            $phone = '+'.$phoneRaw;
        } elseif ($phoneRaw !== '') {
            $phone = '+62'.$phoneRaw;
        }

        $customers = [
            'name' => $name,
            'email' => isset($customer?->email) ? (string) $customer->email : null,
            'phone' => $phone,
        ];

        $itemName = __('bookings.payment.moota_line_item');

        $baseDescription = __('bookings.payment.moota_description', [
            'code' => (string) ($booking->booking_code ?? $booking->getKey()),
        ]);

        $allowedAccounts = $this->client->bankAccountIds();
        $pickMode = strtolower((string) config('services.moota.bank_account_pick', 'first'));

        $explicit = is_string($explicitMootaBankAccountId) ? trim($explicitMootaBankAccountId) : '';

        if ($explicit !== '' && in_array($explicit, $allowedAccounts, true)) {
            $accountId = $explicit;
        } elseif (count($allowedAccounts) > 1 && $pickMode === 'user') {
            throw new RuntimeException(__('bookings.flash.moota_bank_account_required'));
        } else {
            $accountId = $this->client->resolveBankAccountIdForCharge();
        }

        if ($accountId === '') {
            throw new RuntimeException(__('bookings.flash.moota_bank_account_invalid'));
        }
        $expire = max(30, min(10080, (int) config('services.moota.payment_expire_minutes', 1440)));

        $redirectBack = Route::has('bookings.show')
            ? route('bookings.show', $booking, absolute: true)
            : '';

        $idrAmount = (int) $this->currencyService->convertUsdToIdr($payment->gross_amount);

        // Spesifikasi Moota: total wajib; kirim kedua nama field kalau ada perbedaan versi API.
        $payload = [
            'order_id' => $payment->order_id,
            // Hanya salah satu: API menolak jika account_id dan bank_account_id sekaligus.
            'bank_account_id' => $accountId,
            'customers' => $customers,
            'items' => [[
                'name' => $itemName,
                'description' => $baseDescription,
                'qty' => 1,
                'price' => $idrAmount,
            ]],
            'description' => $baseDescription,
            'note' => $booking->booking_code ?? null,
            'total' => $idrAmount,
            'expired_in_minutes' => $expire,
        ];

        if ($redirectBack !== '') {
            $payload['redirect_url'] = $redirectBack;
            $payload['success_redirect_url'] = $redirectBack;
            $payload['failed_redirect_url'] = $redirectBack;
        }

        $json = $this->client->createTransaction($payload);
        /** @var array<string, mixed>|null $data */
        $data = null;
        if (isset($json['data']) && is_array($json['data'])) {
            $data = $json['data'];
        } elseif (isset($json['trx_id'])) {
            /** @phpstan-ignore assign.propertyType */
            $data = $json;
        }

        if (! is_array($data)) {
            PaymentFlowLog::warning('moota.charge.unexpected_payload', []);

            throw new RuntimeException('Respons Moota tidak berisi detail transaksi.');
        }

        $paymentUrlRaw = data_get($data, 'payment_url')
            ?? data_get($json, 'payment_url');

        $paymentUrl = is_string($paymentUrlRaw) ? trim($paymentUrlRaw) : '';
        if ($paymentUrl === '') {
            throw new RuntimeException('Moota tidak mengembalikan payment_url.');
        }

        $trxId = data_get($data, 'trx_id');
        $trxId = is_string($trxId) ? $trxId : null;

        $expiredRaw = data_get($data, 'expired_at');
        $expiryTime = null;
        if (is_string($expiredRaw) && $expiredRaw !== '') {
            $expiryTime = $expiredRaw;
        }

        $totalRaw = data_get($data, 'total')
            ?? data_get($data, 'amount')
            ?? data_get($json, 'total');

        $mootaTotal = null;
        if (is_numeric($totalRaw)) {
            $mootaTotal = (int) round((float) $totalRaw);
        }

        PaymentFlowLog::info('moota.charge.success', [
            'order_id' => $payment->order_id,
            'trx_id' => $trxId,
            'moota_total' => $mootaTotal,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'trx_id' => $trxId,
            'expiry_time' => $expiryTime,
            'moota_total' => $mootaTotal,
            'payload' => $json,
            'normalized_data' => $data,
            'bank_account_id' => $accountId,
        ];
    }
}
