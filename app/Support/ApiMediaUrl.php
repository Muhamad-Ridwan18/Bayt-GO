<?php

namespace App\Support;

use App\Models\MuthowifProfile;
use Illuminate\Support\Facades\Storage;

final class ApiMediaUrl
{
    public static function absolute(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }

    public static function publicDisk(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return self::absolute(Storage::disk('public')->url($path));
    }

    public static function muthowifAvatar(MuthowifProfile $profile, ?string $fallbackName = null): string
    {
        $name = $fallbackName ?? $profile->user?->name ?? 'Muthowif';

        if (! filled($profile->photo_path)) {
            return self::fallbackAvatar($name);
        }

        return self::absolute($profile->photoUrl()) ?? self::fallbackAvatar($name);
    }

    public static function fallbackAvatar(string $name, string $background = '1A3D34'): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background='.$background.'&color=fff';
    }
}
