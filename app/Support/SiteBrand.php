<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

final class SiteBrand
{
    public const SETTING_LOGO_PATH = 'site.logo_path';

    public static function logoStoragePath(): ?string
    {
        $path = SiteSetting::getValue(self::SETTING_LOGO_PATH);

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * URL publik untuk logo (disk `public`), atau null jika tidak ada / file hilang.
     */
    public static function logoPublicUrl(): ?string
    {
        $path = self::logoStoragePath();
        if ($path === null) {
            return null;
        }
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function forgetLogoFile(): void
    {
        $path = self::logoStoragePath();
        if ($path !== null && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        SiteSetting::putValue(self::SETTING_LOGO_PATH, null);
    }
}
