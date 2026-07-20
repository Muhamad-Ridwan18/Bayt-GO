<?php

namespace App\Support;

use App\Services\Moota\MootaApiClient;

final class ApiMootaPaymentMeta
{
    /**
     * @return array{mode: string, is_sandbox: bool, label: string, hint: string}
     */
    public static function environment(): array
    {
        $isSandbox = filter_var(config('services.moota.is_sandbox', false), FILTER_VALIDATE_BOOLEAN);

        return [
            'mode' => $isSandbox ? 'sandbox' : 'production',
            'is_sandbox' => $isSandbox,
            'label' => $isSandbox ? 'Mode Uji (Sandbox)' : 'Produksi',
            'hint' => $isSandbox
                ? 'Akun Moota sandbox — transfer simulasi, bukan rekening bank produksi.'
                : 'Akun Moota produksi — transfer ke rekening bank yang sebenarnya.',
        ];
    }

    /**
     * @param  list<string>  $methods
     * @return list<array<string, mixed>>
     */
    public static function methodsMeta(array $methods): array
    {
        $mootaRows = self::mootaRowsByMethodId($methods);

        return array_map(static function (string $id) use ($mootaRows): array {
            if (preg_match('/^bank_transfer_moota__(\d+)$/', $id, $m)) {
                $base = [
                    'id' => $id,
                    'label' => __('bookings.payment.moota_account_title', ['n' => ((int) $m[1]) + 1]),
                    'group' => 'moota',
                ];

                return array_merge($base, $mootaRows[$id] ?? []);
            }

            if ($id === 'bank_transfer_moota') {
                $base = [
                    'id' => $id,
                    'label' => __('bookings.payment.method_bank_transfer_moota.name'),
                    'group' => 'moota',
                ];

                $merged = array_merge($base, $mootaRows[$id] ?? []);

                return $merged;
            }

            return ['id' => $id, 'label' => $id, 'group' => 'other'];
        }, $methods);
    }

    /**
     * @param  list<string>  $methods
     * @return array<string, array<string, mixed>>
     */
    private static function mootaRowsByMethodId(array $methods): array
    {
        if (BookingSnapPaymentCatalog::driver() !== 'moota') {
            return [];
        }

        /** @var list<string> $accountIds */
        $accountIds = config('services.moota.bank_account_ids', []);
        $client = app(MootaApiClient::class);
        $labels = $client->paymentLabelsForOrderedAccountIds($accountIds);
        $details = $client->bankAccountDetailsByIdMap();

        $out = [];

        foreach ($methods as $id) {
            $index = null;
            if (preg_match('/^bank_transfer_moota__(\d+)$/', $id, $m)) {
                $index = (int) $m[1];
            } elseif ($id === 'bank_transfer_moota') {
                $index = 0;
            }

            if ($index === null) {
                continue;
            }

            $accountId = trim((string) ($accountIds[$index] ?? ''));
            $labelRow = $labels[$index] ?? null;
            $detailRow = $accountId !== '' ? ($details[$accountId] ?? null) : null;

            $bankName = is_array($labelRow) ? trim((string) ($labelRow['name'] ?? '')) : '';
            $holder = is_array($detailRow) ? trim((string) ($detailRow['atas_nama'] ?? '')) : '';
            $accountNumber = is_array($detailRow) ? trim((string) ($detailRow['account_number'] ?? '')) : '';
            $description = is_array($labelRow) ? trim((string) ($labelRow['description'] ?? '')) : '';

            $out[$id] = array_filter([
                'label' => $bankName !== '' ? $bankName : null,
                'bank_name' => $bankName !== '' ? $bankName : null,
                'account_holder' => $holder !== '' ? $holder : null,
                'account_number' => $accountNumber !== '' ? $accountNumber : null,
                'bank_account_ref' => $accountId !== '' ? self::maskAccountRef($accountId) : null,
                'description' => $description !== '' ? $description : null,
                'logo_url' => is_array($labelRow) ? ($labelRow['logo_url'] ?? null) : null,
                'bank_type' => is_array($labelRow) ? ($labelRow['bank_type'] ?? null) : null,
            ], static fn ($v) => $v !== null && $v !== '');
        }

        return $out;
    }

    private static function maskAccountRef(string $accountId): string
    {
        $id = trim($accountId);
        if (strlen($id) <= 8) {
            return $id;
        }

        return substr($id, 0, 4).'…'.substr($id, -4);
    }
}
