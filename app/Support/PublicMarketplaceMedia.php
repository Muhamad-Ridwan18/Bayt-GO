<?php

namespace App\Support;

use App\Models\MuthowifPortfolio;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use Illuminate\Support\Facades\Storage;

final class PublicMarketplaceMedia
{
    private const PROFILE_DIR = 'marketplace/profiles';

    private const PORTFOLIO_IMAGE_DIR = 'marketplace/portfolio-images';

    public static function enabled(): bool
    {
        return (bool) config('marketplace.public_media_enabled', true);
    }

    public static function profilePhotoUrl(MuthowifProfile $profile): ?string
    {
        if (! self::enabled() || ! filled($profile->photo_path) || ! $profile->isApproved()) {
            return null;
        }

        $path = self::publishedProfilePath($profile);
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function portfolioImageUrl(MuthowifPortfolioImage $image): ?string
    {
        if (! self::enabled() || ! filled($image->path)) {
            return null;
        }

        $image->loadMissing('portfolio.muthowifProfile');
        if (! $image->portfolio?->muthowifProfile?->isApproved()) {
            return null;
        }

        $path = self::publishedPortfolioImagePath($image);
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function portfolioCoverUrl(MuthowifPortfolio $portfolio): ?string
    {
        $coverPath = $portfolio->coverImagePath();
        if (! is_string($coverPath) || $coverPath === '') {
            return null;
        }

        $portfolio->loadMissing('muthowifProfile');
        if (! $portfolio->muthowifProfile?->isApproved()) {
            return null;
        }

        if ($portfolio->relationLoaded('images')) {
            $cover = $portfolio->images->first();
            if ($cover instanceof MuthowifPortfolioImage) {
                return self::portfolioImageUrl($cover);
            }
        }

        $firstImage = $portfolio->images()->select(['id', 'path', 'muthowif_portfolio_id'])->first();
        if ($firstImage instanceof MuthowifPortfolioImage) {
            return self::portfolioImageUrl($firstImage);
        }

        if (! self::enabled()) {
            return null;
        }

        $path = self::publishedPortfolioLegacyPath($portfolio, $coverPath);
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function removeProfilePhoto(MuthowifProfile $profile): void
    {
        self::deletePublishedProfile($profile);
    }

    public static function removePortfolioImage(MuthowifPortfolioImage $image): void
    {
        self::deletePublishedPortfolioImage($image);
    }

    public static function syncProfilePhoto(MuthowifProfile $profile): void
    {
        if (! self::enabled()) {
            return;
        }

        if (! filled($profile->photo_path) || ! $profile->isApproved()) {
            self::deletePublishedProfile($profile);

            return;
        }

        self::publishProfilePhoto($profile);
    }

    public static function syncPortfolioImage(MuthowifPortfolioImage $image): void
    {
        if (! self::enabled()) {
            return;
        }

        $image->loadMissing('portfolio.muthowifProfile');
        if (! filled($image->path) || ! $image->portfolio?->muthowifProfile?->isApproved()) {
            self::deletePublishedPortfolioImage($image);

            return;
        }

        self::publishPortfolioImage($image);
    }

    public static function publishProfilePhoto(MuthowifProfile $profile): bool
    {
        if (! filled($profile->photo_path)) {
            return false;
        }

        $source = Storage::disk('local');
        if (! $source->exists($profile->photo_path)) {
            return false;
        }

        $dest = self::publishedProfilePath($profile);
        if ($dest === null) {
            return false;
        }

        return self::copyToPublic($source, $profile->photo_path, $dest);
    }

    public static function publishPortfolioImage(MuthowifPortfolioImage $image): bool
    {
        if (! filled($image->path)) {
            return false;
        }

        $source = Storage::disk('local');
        if (! $source->exists($image->path)) {
            return false;
        }

        $dest = self::publishedPortfolioImagePath($image);
        if ($dest === null) {
            return false;
        }

        return self::copyToPublic($source, $image->path, $dest);
    }

    public static function publishAll(): array
    {
        $stats = ['profiles' => 0, 'portfolio_images' => 0, 'skipped' => 0];

        MuthowifProfile::query()
            ->approved()
            ->whereNotNull('photo_path')
            ->select(['id', 'photo_path', 'verification_status', 'account_status'])
            ->orderBy('id')
            ->chunkById(100, function ($profiles) use (&$stats): void {
                foreach ($profiles as $profile) {
                    if (self::publishProfilePhoto($profile)) {
                        $stats['profiles']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            });

        MuthowifPortfolioImage::query()
            ->select(['id', 'path', 'muthowif_portfolio_id'])
            ->with(['portfolio.muthowifProfile:id,verification_status,account_status'])
            ->orderBy('id')
            ->chunkById(100, function ($images) use (&$stats): void {
                foreach ($images as $image) {
                    if (! $image->portfolio?->muthowifProfile?->isApproved()) {
                        $stats['skipped']++;

                        continue;
                    }

                    if (self::publishPortfolioImage($image)) {
                        $stats['portfolio_images']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            });

        return $stats;
    }

    private static function deletePublishedProfile(MuthowifProfile $profile): void
    {
        $path = self::publishedProfilePath($profile);
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function deletePublishedPortfolioImage(MuthowifPortfolioImage $image): void
    {
        $path = self::publishedPortfolioImagePath($image);
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function publishedProfilePath(MuthowifProfile $profile): ?string
    {
        if (! filled($profile->photo_path)) {
            return null;
        }

        $extension = self::extensionFromPath($profile->photo_path);

        return self::PROFILE_DIR.'/'.$profile->getKey().'.'.$extension;
    }

    private static function publishedPortfolioImagePath(MuthowifPortfolioImage $image): ?string
    {
        if (! filled($image->path)) {
            return null;
        }

        $extension = self::extensionFromPath($image->path);

        return self::PORTFOLIO_IMAGE_DIR.'/'.$image->getKey().'.'.$extension;
    }

    private static function publishedPortfolioLegacyPath(MuthowifPortfolio $portfolio, string $sourcePath): ?string
    {
        $extension = self::extensionFromPath($sourcePath);

        return self::PORTFOLIO_IMAGE_DIR.'/legacy-'.$portfolio->getKey().'.'.$extension;
    }

    private static function extensionFromPath(string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'jpg';
    }

    private static function copyToPublic($sourceDisk, string $sourcePath, string $destPath): bool
    {
        $contents = $sourceDisk->get($sourcePath);
        if (! is_string($contents) || $contents === '') {
            return false;
        }

        return Storage::disk('public')->put($destPath, $contents) !== false;
    }
}
