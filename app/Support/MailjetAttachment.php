<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class MailjetAttachment
{
    /**
     * @return array{ContentType: string, Filename: string, Base64Content: string}|null
     */
    public static function fromPublicDisk(string $path, ?string $filename = null): ?array
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if ($relative === '' || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $binary = Storage::disk('public')->get($relative);
        if ($binary === null || $binary === '') {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';
        $name = $filename ?: basename($relative);

        return [
            'ContentType' => $mime,
            'Filename' => $name,
            'Base64Content' => base64_encode($binary),
        ];
    }

    /**
     * @return array{ContentType: string, Filename: string, Base64Content: string}
     */
    public static function fromBinary(string $binary, string $filename, string $contentType): array
    {
        return [
            'ContentType' => $contentType,
            'Filename' => $filename,
            'Base64Content' => base64_encode($binary),
        ];
    }

    /**
     * @return array{ContentType: string, Filename: string, Base64Content: string}
     */
    public static function fromHtml(string $html, string $filename): array
    {
        return self::fromBinary($html, $filename, 'text/html; charset=utf-8');
    }
}
