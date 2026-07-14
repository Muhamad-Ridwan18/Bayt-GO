<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

final class UploadedImageOptimizer
{
    private ?ImageManager $manager = null;

    public function enabled(): bool
    {
        return (bool) config('image-upload.enabled', true);
    }

    /**
     * Simpan file upload; gambar raster dikompres, selain itu disimpan apa adanya.
     */
    public function store(
        UploadedFile $file,
        string $directory,
        string $disk = 'local',
        string $profileKey = 'default',
    ): string {
        if (! $this->enabled() || ! $this->isOptimizableImage($file)) {
            return $this->storeRawWithExtension($file, $directory, $disk);
        }

        $encoded = $this->encodeUploaded($file, $profileKey);
        if ($encoded === null) {
            return $this->storeRawWithExtension($file, $directory, $disk);
        }

        $extension = (string) config('image-upload.format', 'jpg');
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        Storage::disk($disk)->put($path, $encoded);

        return $path;
    }

    /**
     * Simpan tanpa kompres, selalu dengan ekstensi yang bisa ditebak.
     * `$file->store()` Laravel memakai hashName(); bila guessExtension() kosong
     * (sering di upload FormData React Native), path jadi tanpa ekstensi.
     */
    private function storeRawWithExtension(UploadedFile $file, string $directory, string $disk): string
    {
        $extension = $this->resolveExtension($file);
        $filename = Str::random(40).($extension !== '' ? '.'.$extension : '');
        $stored = $file->storeAs(trim($directory, '/'), $filename, $disk);

        return $stored !== false ? $stored : '';
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $guessed = $file->guessExtension() ?: $file->clientExtension();
        if (is_string($guessed) && trim($guessed) !== '') {
            return strtolower(trim($guessed));
        }

        $fromName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION);
        if (is_string($fromName) && trim($fromName) !== '') {
            return strtolower(trim($fromName));
        }

        return match (strtolower((string) $file->getMimeType())) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    /**
     * Kompres ulang file yang sudah ada di disk (mis. setelah konversi HEIC).
     */
    public function optimizeStoredPath(string $path, string $disk = 'local', string $profileKey = 'default'): string
    {
        if (! $this->enabled() || $path === '') {
            return $path;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            return $path;
        }

        $mime = $storage->mimeType($path);
        if (! is_string($mime) || ! $this->isOptimizableMime($mime)) {
            return $path;
        }

        try {
            $image = $this->manager()->read($storage->path($path));
            $encoded = $this->encodeImage($image, $profileKey);
            if ($encoded === null) {
                return $path;
            }

            $extension = (string) config('image-upload.format', 'jpg');
            $newPath = preg_replace('/\.[^.]+$/', '', $path).'.'.$extension;
            if ($newPath === null || $newPath === '') {
                $newPath = $path.'.'.$extension;
            }

            $storage->put($newPath, $encoded);
            if ($newPath !== $path) {
                $storage->delete($path);
            }

            return $newPath;
        } catch (\Throwable $e) {
            Log::warning('UploadedImageOptimizer: optimizeStoredPath failed', [
                'path' => $path,
                'disk' => $disk,
                'message' => $e->getMessage(),
            ]);

            return $path;
        }
    }

    public function isOptimizableImage(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();

        return is_string($mime) && $this->isOptimizableMime($mime);
    }

    public function isOptimizableMime(string $mime): bool
    {
        return in_array(strtolower($mime), [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/bmp',
        ], true);
    }

    /**
     * @return array{max_width: int, max_height: int, quality: int}
     */
    private function profile(string $profileKey): array
    {
        $profiles = config('image-upload.profiles', []);
        $defaults = is_array($profiles['default'] ?? null) ? $profiles['default'] : [];
        $chosen = is_array($profiles[$profileKey] ?? null) ? $profiles[$profileKey] : [];

        return [
            'max_width' => (int) ($chosen['max_width'] ?? $defaults['max_width'] ?? 1920),
            'max_height' => (int) ($chosen['max_height'] ?? $defaults['max_height'] ?? 1920),
            'quality' => max(40, min(95, (int) ($chosen['quality'] ?? $defaults['quality'] ?? 82))),
        ];
    }

    private function manager(): ImageManager
    {
        if ($this->manager !== null) {
            return $this->manager;
        }

        $driver = extension_loaded('imagick')
            ? new ImagickDriver
            : new GdDriver;

        $this->manager = new ImageManager($driver);

        return $this->manager;
    }

    private function encodeUploaded(UploadedFile $file, string $profileKey): ?string
    {
        try {
            $image = $this->manager()->read($file->getRealPath() ?: $file->getPathname());

            return $this->encodeImage($image, $profileKey);
        } catch (\Throwable $e) {
            Log::warning('UploadedImageOptimizer: encodeUploaded failed', [
                'original' => $file->getClientOriginalName(),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function encodeImage(ImageInterface $image, string $profileKey): ?string
    {
        $profile = $this->profile($profileKey);

        $image->scaleDown(
            width: $profile['max_width'],
            height: $profile['max_height'],
        );

        return (string) $image->toJpeg(quality: $profile['quality']);
    }
}
