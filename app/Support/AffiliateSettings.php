<?php

namespace App\Support;

use App\Models\SiteSetting;

final class AffiliateSettings
{
    public const TIERS_KEY = 'affiliate_commission_tiers';

    public const RATE_KEY = 'affiliate_commission_rate';

    public const MIN_WITHDRAW_KEY = 'affiliate_min_withdraw';

    public const DEFAULT_MIN_WITHDRAW = 100000.0;

    /**
     * @var list<array{min: float, rate: float}>
     */
    public const DEFAULT_TIERS = [
        ['min' => 0.0, 'rate' => 0.01],
        ['min' => 250_000_000.0, 'rate' => 0.015],
        ['min' => 500_000_000.0, 'rate' => 0.02],
    ];

    /**
     * @return list<array{min: float, rate: float, label: string, level: int}>
     */
    public static function getTiers(): array
    {
        $raw = SiteSetting::getValue(self::TIERS_KEY);
        $tiers = self::DEFAULT_TIERS;

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && $decoded !== []) {
                $normalized = self::normalizeTiers($decoded);
                if ($normalized !== []) {
                    $tiers = $normalized;
                }
            }
        }

        return self::withLabels($tiers);
    }

    /**
     * @param  list<array{min: float|int|string, rate: float|int|string}>  $tiers
     */
    public static function putTiers(array $tiers): void
    {
        $normalized = self::normalizeTiers($tiers);
        if ($normalized === []) {
            $normalized = self::DEFAULT_TIERS;
        }

        SiteSetting::putValue(self::TIERS_KEY, json_encode(array_map(
            static fn (array $t): array => [
                'min' => $t['min'],
                'rate' => $t['rate'],
            ],
            $normalized
        )));

        // Keep legacy key in sync with level-1 rate for older readers.
        SiteSetting::putValue(self::RATE_KEY, (string) $normalized[0]['rate']);
    }

    public static function getRateForVolume(float $volume): float
    {
        return self::resolveLevel($volume)['rate'];
    }

    /**
     * Level-1 rate (volume 0). Prefer getRateForVolume / resolveLevel for attribution.
     */
    public static function getRate(): float
    {
        return self::getRateForVolume(0.0);
    }

    /**
     * @return array{
     *     level: int,
     *     rate: float,
     *     min: float,
     *     next_min: float|null,
     *     label: string
     * }
     */
    public static function resolveLevel(float $volume): array
    {
        $tiers = self::getTiers();
        $volume = max(0.0, $volume);
        $matched = $tiers[0];

        foreach ($tiers as $tier) {
            if ($volume >= $tier['min']) {
                $matched = $tier;
            }
        }

        $nextMin = null;
        foreach ($tiers as $tier) {
            if ($tier['min'] > $matched['min']) {
                $nextMin = $tier['min'];
                break;
            }
        }

        return [
            'level' => $matched['level'],
            'rate' => $matched['rate'],
            'min' => $matched['min'],
            'next_min' => $nextMin,
            'label' => $matched['label'],
        ];
    }

    public static function getMinWithdraw(): float
    {
        return (float) SiteSetting::getValue(self::MIN_WITHDRAW_KEY, (string) self::DEFAULT_MIN_WITHDRAW);
    }

    public static function putRate(float $rate): void
    {
        // Legacy: update level-1 rate only.
        $tiers = self::getTiers();
        $tiers[0]['rate'] = round($rate, 6);
        self::putTiers($tiers);
    }

    public static function putMinWithdraw(float $amount): void
    {
        SiteSetting::putValue(self::MIN_WITHDRAW_KEY, (string) round($amount, 2));
    }

    /**
     * @param  list<array{min?: mixed, rate?: mixed}>  $tiers
     * @return list<array{min: float, rate: float}>
     */
    private static function normalizeTiers(array $tiers): array
    {
        $out = [];
        foreach ($tiers as $tier) {
            if (! is_array($tier)) {
                continue;
            }
            if (! array_key_exists('min', $tier) || ! array_key_exists('rate', $tier)) {
                continue;
            }
            $out[] = [
                'min' => round((float) $tier['min'], 2),
                'rate' => round((float) $tier['rate'], 6),
            ];
        }

        if ($out === []) {
            return [];
        }

        usort($out, static fn (array $a, array $b): int => $a['min'] <=> $b['min']);

        // Ensure first tier starts at 0.
        $out[0]['min'] = 0.0;

        return array_values($out);
    }

    /**
     * @param  list<array{min: float, rate: float}>  $tiers
     * @return list<array{min: float, rate: float, label: string, level: int}>
     */
    private static function withLabels(array $tiers): array
    {
        $labeled = [];
        foreach (array_values($tiers) as $i => $tier) {
            $level = $i + 1;
            $labeled[] = [
                'min' => $tier['min'],
                'rate' => $tier['rate'],
                'level' => $level,
                'label' => 'Level '.$level,
            ];
        }

        return $labeled;
    }
}
