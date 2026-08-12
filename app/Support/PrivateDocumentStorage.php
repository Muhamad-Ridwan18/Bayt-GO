<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dokumen identitas (KTP, supporting docs): prefer disk local; fallback public untuk legacy API upload.
 */
final class PrivateDocumentStorage
{
    /**
     * @return list<string>
     */
    public static function disks(): array
    {
        return ['local', 'public'];
    }

    public static function exists(?string $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        foreach (self::disks() as $diskName) {
            if (Storage::disk($diskName)->exists($path)) {
                return true;
            }
        }

        return false;
    }

    public static function response(?string $path, ?string $name = null): StreamedResponse
    {
        if (! is_string($path) || $path === '') {
            abort(404);
        }

        foreach (self::disks() as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                return $disk->response($path, $name);
            }
        }

        abort(404);
    }

    public static function delete(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        foreach (self::disks() as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
