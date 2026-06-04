<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class StoredImageResponse
{
    /**
     * @param  'public'|'private'  $visibility
     */
    public static function fromDisk(
        string $disk,
        string $path,
        ?string $downloadName = null,
        string $visibility = 'public',
    ): Response {
        $filesystem = Storage::disk($disk);
        if (! $filesystem->exists($path)) {
            abort(404);
        }

        $etag = self::etag($filesystem, $disk, $path);
        if (request()->headers->get('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => self::cacheControl($visibility),
            ]);
        }

        $response = $filesystem->response($path, $downloadName);
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', self::cacheControl($visibility));

        return $response;
    }

    private static function etag(Filesystem $filesystem, string $disk, string $path): string
    {
        $lastModified = 0;
        try {
            $lastModified = $filesystem->lastModified($path);
        } catch (\Throwable) {
            $fullPath = method_exists($filesystem, 'path') ? $filesystem->path($path) : null;
            if (is_string($fullPath) && is_file($fullPath)) {
                $lastModified = (int) filemtime($fullPath);
            }
        }

        return '"'.hash('xxh128', $disk.'|'.$path.'|'.$lastModified).'"';
    }

    private static function cacheControl(string $visibility): string
    {
        return $visibility === 'public'
            ? 'public, max-age=86400, stale-while-revalidate=604800'
            : 'private, max-age=3600';
    }
}
