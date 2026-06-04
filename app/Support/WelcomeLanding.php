<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class WelcomeLanding
{
    public const SETTING_EXTERNAL_URL = 'welcome.hero_external_url';

    public const SETTING_STORAGE_PATH = 'welcome.hero_storage_path';

    public const SETTING_OBJECT_BASE = 'welcome.hero_object_position_base';

    public const SETTING_OBJECT_SM = 'welcome.hero_object_position_sm';

    public const SETTING_OBJECT_LG = 'welcome.hero_object_position_lg';

    public static function externalUrlRaw(): ?string
    {
        $v = SiteSetting::getValue(self::SETTING_EXTERNAL_URL);

        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }

    public static function storagePath(): ?string
    {
        $path = SiteSetting::getValue(self::SETTING_STORAGE_PATH);

        return is_string($path) && $path !== '' ? $path : null;
    }

    public static function uploadedHeroPublicUrl(): ?string
    {
        $path = self::storagePath();
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /** @return array{base: string, sm: string, lg: string} */
    public static function objectPositions(): array
    {
        $defaults = config('welcome.hero.object_position');

        return [
            'base' => self::positionOrDefault(self::SETTING_OBJECT_BASE, (string) $defaults['base']),
            'sm' => self::positionOrDefault(self::SETTING_OBJECT_SM, (string) $defaults['sm']),
            'lg' => self::positionOrDefault(self::SETTING_OBJECT_LG, (string) $defaults['lg']),
        ];
    }

    private static function positionOrDefault(string $settingKey, string $fallback): string
    {
        $v = SiteSetting::getValue($settingKey);
        if (! is_string($v) || trim($v) === '') {
            return $fallback;
        }

        return trim($v);
    }

    public static function tailwindObjectPositionClass(?string $v): string
    {
        $raw = strtolower(trim((string) $v));
        if ($raw === '') {
            return 'object-center';
        }

        $words = preg_split('/[\s_-]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $h = null;
        $vert = null;
        foreach ($words as $w) {
            if (in_array($w, ['left', 'right'], true) && $h === null) {
                $h = $w;
            }
            if (in_array($w, ['top', 'bottom'], true) && $vert === null) {
                $vert = $w;
            }
        }
        foreach ($words as $w) {
            if ($w !== 'center') {
                continue;
            }
            if ($h === null && $vert === null) {
                return 'object-center';
            }
            if ($h === null) {
                $h = 'center';
            } elseif ($vert === null) {
                $vert = 'center';
            }
        }
        if ($h !== null && $vert !== null) {
            return 'object-'.$h.'-'.$vert;
        }
        if ($h !== null) {
            return 'object-'.$h;
        }
        if ($vert !== null) {
            return 'object-'.$vert;
        }

        $normalized = preg_replace('/\s*,\s*/', ',', trim((string) $v));
        if (preg_match('/[<>\'\"\\\\]/', $normalized)) {
            return 'object-center';
        }
        if (preg_match('/^[\d.%pxemrem,\s-]+$/i', $normalized) !== 1 || ! preg_match('/\d/', $normalized)) {
            return 'object-center';
        }

        return 'object-['.str_replace(',', '_', preg_replace('/\s+/', '_', $normalized)).']';
    }

    public static function heroImageTailwindClasses(): string
    {
        $positions = self::objectPositions();

        return implode(' ', array_filter([
            'h-full w-full min-h-[36rem] object-cover',
            self::tailwindObjectPositionClass($positions['base']),
            'sm:min-h-[40rem]',
            'sm:'.self::tailwindObjectPositionClass($positions['sm']),
            'lg:min-h-[44rem]',
            'lg:'.self::tailwindObjectPositionClass($positions['lg']),
        ]));
    }

    public static function resolvedHeroImageUrl(): string
    {
        $url = self::externalUrlRaw();
        if ($url !== null && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $uploaded = self::uploadedHeroPublicUrl();
        if ($uploaded !== null) {
            return $uploaded;
        }

        return self::discoveredPublicHeroUrl() ?? (string) config('welcome.hero.fallback_image');
    }

    private static function discoveredPublicHeroUrl(): ?string
    {
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
            $p = public_path('images/bg-welcome.'.$ext);
            if (file_exists($p)) {
                return asset('images/bg-welcome.'.$ext);
            }
        }

        $welcomeBgDir = public_path('images/bg-welcome');
        if (is_dir($welcomeBgDir)) {
            $entries = array_diff(scandir($welcomeBgDir) ?: [], ['.', '..']);
            sort($entries, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($entries as $name) {
                $lower = strtolower($name);
                if (preg_match('/\.(jpe?g|png|webp)$/i', $lower)) {
                    return asset('images/bg-welcome/'.$name);
                }
            }
        }

        if (file_exists(public_path('images/welcome-hero.jpg'))) {
            return asset('images/welcome-hero.jpg');
        }

        return null;
    }

    public static function forgetCustomHero(): void
    {
        $path = self::storagePath();
        if ($path !== null && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        SiteSetting::putValue(self::SETTING_STORAGE_PATH, null);
        SiteSetting::putValue(self::SETTING_EXTERNAL_URL, null);
    }

    public static function storeUploadedHero(UploadedFile $file): void
    {
        $old = self::storagePath();
        if ($old !== null && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        $stored = app(UploadedImageOptimizer::class)->store($file, 'site', 'public', 'banner');
        SiteSetting::putValue(self::SETTING_STORAGE_PATH, $stored);
    }

    public static function saveExternalUrl(?string $url): void
    {
        $trimmed = is_string($url) ? trim($url) : '';
        SiteSetting::putValue(self::SETTING_EXTERNAL_URL, $trimmed === '' ? null : $trimmed);
    }

    /** @param  array{base?: string|null, sm?: string|null, lg?: string|null}  $positions */
    public static function saveObjectPositions(array $positions): void
    {
        $map = [
            self::SETTING_OBJECT_BASE => $positions['base'] ?? null,
            self::SETTING_OBJECT_SM => $positions['sm'] ?? null,
            self::SETTING_OBJECT_LG => $positions['lg'] ?? null,
        ];

        foreach ($map as $key => $val) {
            $s = is_string($val) ? trim($val) : '';
            SiteSetting::putValue($key, $s === '' ? null : $s);
        }
    }
}
