<?php

namespace App\Services;

use App\Models\MuthowifWithdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransPayoutService
{
    public function isConfigured(): bool
    {
        $server = config('services.midtrans.server_key');

        return is_string($server) && $server !== '';
    }

    public function payoutBaseUrl(): string
    {
        return config('services.midtrans.is_production', false)
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * Create payout request (Midtrans Disbursement).
     *
     * @return array{status: string, reference_no: string|null}
     *
     * @throws RuntimeException
     */
    public function createPayout(MuthowifWithdrawal $withdrawal): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        $withdrawal->loadMissing(['muthowifProfile.user']);

        // Midtrans payout amount expects numeric string with .0 fraction.
        // We send whole number only (IDR), since wallet_balance uses 2 decimals but payouts are usually integer.
        $amountWhole = (int) round((float) $withdrawal->amount);
        $amountStr = (string) $amountWhole.'.0';

        $notes = $withdrawal->notes ?: ('Withdraw '.$withdrawal->getKey());

        $payload = [
            'payouts' => [
                [
                    'beneficiary_name' => (string) $withdrawal->beneficiary_name,
                    'beneficiary_account' => (string) $withdrawal->beneficiary_account,
                    'beneficiary_bank' => (string) $withdrawal->beneficiary_bank,
                    'beneficiary_email' => $withdrawal->muthowifProfile?->user?->email,
                    'amount' => $amountStr,
                    'notes' => $notes,
                ],
            ],
        ];

        $response = Http::timeout(45)
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($this->payoutBaseUrl().'/api/v1/payouts', $payload);

        if (! $response->successful()) {
            Log::warning('Midtrans payout gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
                'withdrawal_id' => $withdrawal->getKey(),
            ]);

            throw new RuntimeException('Gagal membuat payout Midtrans. Coba lagi.');
        }

        $json = $response->json();

        $payoutItem = null;
        if (is_array($json['payouts'] ?? null) && count($json['payouts']) > 0) {
            $payoutItem = $json['payouts'][0];
        }

        $status = (string) ($payoutItem['status'] ?? '');
        $referenceNo = $payoutItem['reference_no'] ?? null;

        if ($status === '') {
            Log::warning('Midtrans payout respons tanpa status', [
                'withdrawal_id' => $withdrawal->getKey(),
                'body' => $json,
            ]);
        }

        return [
            'status' => $status,
            'reference_no' => is_string($referenceNo) ? $referenceNo : null,
        ];
    }
}

