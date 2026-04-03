<?php

namespace App\Services;

use App\Models\MuthowifWithdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditDisbursementService
{
    public function isConfigured(): bool
    {
        $key = config('services.xendit.api_key');

        return is_string($key) && $key !== '';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.xendit.base_url', 'https://api.xendit.co'), '/');
    }

    /**
     * Create disbursement payout request.
     *
     * @return array{id: string, status: string, reference_id: string|null}
     */
    public function createDisbursement(MuthowifWithdrawal $withdrawal): array
    {
        $key = config('services.xendit.api_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('XENDIT_API_KEY belum diatur.');
        }

        $withdrawal->loadMissing(['muthowifProfile.user']);

        // channel_code is taken from beneficiary_bank field (e.g., ID_BCA, ID_DANA, etc).
        $channelCode = trim((string) $withdrawal->beneficiary_bank);
        if ($channelCode === '') {
            throw new RuntimeException('beneficiary_bank (channel_code) kosong.');
        }

        $payload = [
            'reference_id' => (string) $withdrawal->getKey(),
            'channel_code' => $channelCode,
            'channel_properties' => [
                'account_number' => (string) $withdrawal->beneficiary_account,
                'account_holder_name' => (string) $withdrawal->beneficiary_name,
            ],
            'amount' => (float) $withdrawal->amount,
            'currency' => 'IDR',
            'description' => $withdrawal->notes ?: ('Withdraw '.$withdrawal->getKey()),
            'metadata' => [
                'withdrawal_id' => (string) $withdrawal->getKey(),
            ],
        ];

        $response = Http::timeout(45)
            ->withBasicAuth($key, '')
            ->acceptJson()
            ->post($this->baseUrl().'/v2/payouts', $payload);

        if (! $response->successful()) {
            Log::warning('Xendit disbursement gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
                'withdrawal_id' => (string) $withdrawal->getKey(),
            ]);
            throw new RuntimeException('Gagal membuat payout/disbursement Xendit.');
        }

        $json = $response->json();

        return [
            'id' => is_string($json['id'] ?? null) ? (string) $json['id'] : '',
            'status' => is_string($json['status'] ?? null) ? (string) $json['status'] : '',
            'reference_id' => is_string($json['reference_id'] ?? null) ? (string) $json['reference_id'] : null,
        ];
    }
}

