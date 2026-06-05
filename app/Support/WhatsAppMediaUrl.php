<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class WhatsAppMediaUrl
{
    public const BROADCAST_DIR = 'whatsapp-broadcast';

    /**
     * Path relatif disk public untuk bulan berjalan, mis. whatsapp-broadcast/2026-06.
     */
    public static function broadcastStorageDir(?string $month = null): string
    {
        $month ??= now()->format('Y-m');

        return self::BROADCAST_DIR.'/'.$month;
    }

    /**
     * Pastikan folder whatsapp-broadcast (dan subfolder bulan) ada di storage/app/public.
     */
    public static function ensureBroadcastStorageReady(?string $month = null): string
    {
        $dir = self::broadcastStorageDir($month);
        Storage::disk('public')->makeDirectory($dir);

        return $dir;
    }

    /**
     * Base URL yang bisa dijangkau WSM untuk mengunduh lampiran (bukan localhost).
     */
    public static function baseUrl(): string
    {
        $configured = config('services.fonnte.media_public_url');

        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(trim($configured), '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    /**
     * URL absolut untuk berkas di disk public (path relatif root disk, mis. whatsapp-broadcast/...).
     */
    public static function forPublicDiskPath(string $path): string
    {
        $relative = ltrim($path, '/');
        $storageUrl = Storage::disk('public')->url($relative);

        if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
            return $storageUrl;
        }

        return self::baseUrl().'/'.ltrim($storageUrl, '/');
    }

    public static function isPubliclyReachable(?string $url = null): bool
    {
        $url ??= self::baseUrl();

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! self::isPrivateIp($host);
        }

        return true;
    }

    private static function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
