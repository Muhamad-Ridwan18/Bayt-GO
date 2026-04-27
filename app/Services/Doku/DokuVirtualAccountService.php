<?php

namespace App\Services\Doku;

use App\Models\BookingPayment;
use App\Support\PaymentFlowLog;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Jokul direct Virtual Account (DOKU-generated payment code) per bank.
 *
 * @see https://developers.doku.com (bank-specific /{bank}-virtual-account/v2/payment-code)
 */
final class DokuVirtualAccountService
{
    public function __construct(
        private readonly DokuApiClient $api,
    ) {}

    /** @var array<string, non-empty-string> */
    private const PATH_BY_METHOD = [
        'va_bca' => '/bca-virtual-account/v2/payment-code',
        'va_bni' => '/bni-virtual-account/v2/payment-code',
        'va_bri' => '/bri-virtual-account/v2/payment-code',
        'va_permata' => '/permata-virtual-account/v2/payment-code',
        'va_mandiri_bill' => '/mandiri-virtual-account/v2/payment-code',
    ];

    /**
     * @return array{
     *   transaction_id: string|null,
     *   va_bank: string|null,
     *   va_number: string|null,
     *   bill_key: string|null,
     *   biller_code: string|null,
     *   expiry_time: string|null
     * }
     */
    public function createPaymentCode(BookingPayment $payment, string $method): array
    {
        $path = self::PATH_BY_METHOD[$method] ?? null;
        if ($path === null) {
            throw new RuntimeException('Metode VA tidak didukung untuk DOKU direct.');
        }

        PaymentFlowLog::info('doku.va.request', [
            'invoice_number' => $payment->order_id,
            'method' => $method,
            'path' => $path,
            'gross_amount' => $payment->gross_amount,
        ]);

        $minutes = (int) config('services.doku.va_expire_minutes', 60);
        if ($minutes < 1) {
            $minutes = 60;
        }

        $payment->loadMissing(['muthowifBooking.customer']);
        $customer = $payment->muthowifBooking?->customer;
        $name = Str::limit(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', (string) ($customer?->name ?? 'Jamaah')), 64, '');
        if ($name === '') {
            $name = 'Jamaah';
        }

        $body = [
            'order' => [
                'invoice_number' => $payment->order_id,
                'amount' => (int) $payment->gross_amount,
            ],
            'virtual_account_info' => [
                'billing_type' => 'FIX_BILL',
                'expired_time' => $minutes,
                'reusable_status' => false,
                'info1' => 'BaytGo Booking',
            ],
            'customer' => array_filter([
                'name' => $name,
                'email' => $customer?->email,
            ]),
        ];

        $json = $this->api->postJson($path, $body);

        $vaNo = $this->firstString(
            data_get($json, 'virtual_account_info.virtual_account_number'),
            data_get($json, 'response.virtual_account_info.virtual_account_number'),
        );

        if ($vaNo === null || $vaNo === '') {
            throw new RuntimeException('DOKU tidak mengembalikan nomor virtual account.');
        }

        $expiryUtc = $this->firstString(
            data_get($json, 'virtual_account_info.expired_date_utc'),
            data_get($json, 'response.virtual_account_info.expired_date_utc'),
            data_get($json, 'virtual_account_info.expired_date'),
        );
        $expiryTime = null;
        if (is_string($expiryUtc) && $expiryUtc !== '') {
            try {
                $expiryTime = Carbon::parse($expiryUtc)->toIso8601String();
            } catch (\Throwable) {
                $expiryTime = null;
            }
        }
        if ($expiryTime === null) {
            $expiryTime = now()->addMinutes($minutes)->toIso8601String();
        }

        $billKey = $this->firstString(
            data_get($json, 'virtual_account_info.bill_key'),
            data_get($json, 'response.virtual_account_info.bill_key'),
        );
        $billerCode = $this->firstString(
            data_get($json, 'virtual_account_info.biller_code'),
            data_get($json, 'response.virtual_account_info.biller_code'),
        );

        $trxId = $this->firstString(
            data_get($json, 'transaction.id'),
            data_get($json, 'response.transaction.id'),
            data_get($json, 'uuid'),
        );

        $bankLabel = str_replace(['va_', '_bill'], ['', ''], $method);

        PaymentFlowLog::info('doku.va.response_ok', [
            'invoice_number' => $payment->order_id,
            'method' => $method,
            'transaction_id' => $trxId,
            'has_va_number' => $vaNo !== '',
        ]);

        return [
            'transaction_id' => $trxId,
            'va_bank' => $bankLabel !== '' ? $bankLabel : null,
            'va_number' => $vaNo,
            'bill_key' => $billKey,
            'biller_code' => $billerCode,
            'expiry_time' => $expiryTime,
        ];
    }

    private function firstString(mixed ...$candidates): ?string
    {
        foreach ($candidates as $c) {
            if (is_string($c) && $c !== '') {
                return $c;
            }
        }

        return null;
    }
}
