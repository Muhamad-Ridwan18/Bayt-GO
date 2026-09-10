<?php

namespace App\Support;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifSupportPackage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Statistik marketplace nyata untuk prompt automation konten.
 *
 * Angka ini yang membedakan artikel BaytGo dari konten AI generik: diambil dari
 * profil terverifikasi dan harga layanan yang benar-benar ada di database.
 */
final class MarketplaceArticleFacts
{
    private const CACHE_KEY = 'seo:marketplace_article_facts';

    private const CACHE_SECONDS = 600;

    /** Harga di luar rentang ini dianggap outlier/data uji dan tidak ikut statistik. */
    private const MIN_SANE_DAILY_PRICE = 100_000;

    private const MAX_SANE_DAILY_PRICE = 15_000_000;

    private const MIN_SANE_PACKAGE_PRICE = 50_000;

    private const MAX_SANE_PACKAGE_PRICE = 20_000_000;

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => self::compute());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Teks siap tempel ke prompt penulis.
     */
    public static function promptText(?array $facts = null): string
    {
        $facts ??= self::all();

        $lines = [
            'DATA MARKETPLACE BAYTGO (sumber database, bukan dugaan):',
            '- Tanggal data: '.$facts['as_of'],
            '- Muthowif terverifikasi aktif: '.$facts['approved_muthowif_count'],
            '- Yang punya harga layanan di direktori: '.$facts['marketplace_ready_count'],
        ];

        if ($facts['work_locations'] !== []) {
            $locs = array_map(
                static fn (array $row): string => $row['label'].' ('.$row['count'].')',
                $facts['work_locations'],
            );
            $lines[] = '- Lokasi kerja: '.implode(', ', $locs);
        }

        $daily = $facts['service_daily_price'];
        if (($daily['sample_size'] ?? 0) > 0) {
            $lines[] = sprintf(
                '- Harga layanan muthowif per hari (IDR): min %s · median %s · max %s (dari %d layanan)',
                self::rupiah((int) $daily['min']),
                self::rupiah((int) $daily['median']),
                self::rupiah((int) $daily['max']),
                (int) $daily['sample_size'],
            );
        }

        foreach (['group' => 'grup', 'private' => 'private'] as $key => $label) {
            $band = $facts['service_daily_price_by_type'][$key] ?? null;
            if (! is_array($band) || ($band['sample_size'] ?? 0) < 1) {
                continue;
            }
            $lines[] = sprintf(
                '- Harga %s per hari: %s – %s IDR',
                $label,
                self::rupiah((int) $band['min']),
                self::rupiah((int) $band['max']),
            );
        }

        $lines[] = '- Paket pendukung aktif: '.$facts['active_support_package_count'];

        $support = $facts['support_package_price'];
        if (($support['sample_size'] ?? 0) > 0) {
            $lines[] = sprintf(
                '- Harga paket pendukung (IDR): %s – %s',
                self::rupiah((int) $support['min']),
                self::rupiah((int) $support['max']),
            );
        }

        $lines[] = '- Direktori: '.$facts['directory_path'];
        $lines[] = '- Paket pendukung: '.$facts['support_packages_path'];
        $lines[] = '';
        $lines[] = 'ATURAN PENGGUNAAN DATA:';
        $lines[] = '1) Sisipkan 1–3 fakta di atas secara natural (jangan daftar bullet statistik).';
        $lines[] = '2) Jangan mengarang angka lain tentang BaytGo (jumlah muthowif, harga, kota).';
        $lines[] = '3) Tulis harga sebagai kisaran, bukan janji harga tetap.';
        $lines[] = '4) Arahkan pembaca ke '.$facts['directory_path'].' bila relevan.';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private static function compute(): array
    {
        $approved = MuthowifProfile::query()->approved()->get(['id', 'work_location']);
        $approvedIds = $approved->modelKeys();

        $readyCount = MuthowifProfile::query()
            ->approved()
            ->whereHas('services', fn ($q) => $q->where('daily_price', '>', 0))
            ->count();

        $locations = [];
        foreach ($approved as $profile) {
            $label = $profile->workLocationLabel();
            if ($label === null || $label === '') {
                continue;
            }
            $locations[$label] = ($locations[$label] ?? 0) + 1;
        }
        arsort($locations);
        $workLocations = [];
        foreach (array_slice($locations, 0, 8, true) as $label => $count) {
            $workLocations[] = ['label' => $label, 'count' => $count];
        }

        $pricedServices = $approvedIds === []
            ? collect()
            : MuthowifService::query()
                ->whereIn('muthowif_profile_id', $approvedIds)
                ->whereBetween('daily_price', [self::MIN_SANE_DAILY_PRICE, self::MAX_SANE_DAILY_PRICE])
                ->get(['daily_price', 'type']);

        $allPrices = $pricedServices
            ->map(fn (MuthowifService $s) => (int) round((float) $s->daily_price))
            ->filter(fn (int $p) => $p > 0)
            ->sort()
            ->values();

        $byType = [];
        foreach ([MuthowifServiceType::Group, MuthowifServiceType::PrivateJamaah] as $type) {
            $values = $pricedServices
                ->filter(fn (MuthowifService $s) => $s->type === $type)
                ->map(fn (MuthowifService $s) => (int) round((float) $s->daily_price))
                ->sort()
                ->values();
            $byType[$type->value] = self::priceBand($values);
        }

        $packages = MuthowifSupportPackage::query()
            ->where('is_active', true)
            ->whereHas('muthowifProfile', fn ($q) => $q->approved())
            ->whereBetween('price', [self::MIN_SANE_PACKAGE_PRICE, self::MAX_SANE_PACKAGE_PRICE])
            ->get(['price']);

        $packagePrices = $packages
            ->map(fn (MuthowifSupportPackage $p) => (int) round((float) $p->price))
            ->filter(fn (int $p) => $p > 0)
            ->sort()
            ->values();

        $facts = [
            'as_of' => Carbon::now()->toDateString(),
            'currency' => 'IDR',
            'approved_muthowif_count' => $approved->count(),
            'marketplace_ready_count' => $readyCount,
            'work_locations' => $workLocations,
            'service_daily_price' => self::priceBand($allPrices),
            'service_daily_price_by_type' => $byType,
            'active_support_package_count' => MuthowifSupportPackage::query()
                ->where('is_active', true)
                ->whereHas('muthowifProfile', fn ($q) => $q->approved())
                ->count(),
            'support_package_price' => self::priceBand($packagePrices),
            'directory_path' => '/layanan',
            'support_packages_path' => '/layanan-pendukung',
        ];

        $facts['prompt_text'] = self::promptText($facts);

        return $facts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $sorted
     * @return array{min: ?int, median: ?int, max: ?int, sample_size: int}
     */
    private static function priceBand($sorted): array
    {
        $count = $sorted->count();

        if ($count === 0) {
            return ['min' => null, 'median' => null, 'max' => null, 'sample_size' => 0];
        }

        $mid = intdiv($count, 2);
        $median = $count % 2 === 1
            ? (int) $sorted[$mid]
            : (int) round(((int) $sorted[$mid - 1] + (int) $sorted[$mid]) / 2);

        return [
            'min' => (int) $sorted->first(),
            'median' => $median,
            'max' => (int) $sorted->last(),
            'sample_size' => $count,
        ];
    }

    private static function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
