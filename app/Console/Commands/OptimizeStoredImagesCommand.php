<?php

namespace App\Console\Commands;

use App\Models\BookingChatMessage;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportingDocument;
use App\Services\UploadedImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeStoredImagesCommand extends Command
{
    protected $signature = 'images:optimize-existing
                            {--disk=local : Disk penyimpanan}
                            {--profile=document : Profil kompresi (profile|document|portfolio|chat|banner)}
                            {--limit=200 : Maksimum file per eksekusi}
                            {--dry-run : Hanya tampilkan path tanpa menulis}';

    protected $description = 'Kompres ulang gambar yang sudah tersimpan (JPEG resize) untuk menghemat storage';

    public function handle(UploadedImageOptimizer $optimizer): int
    {
        if (! $optimizer->enabled()) {
            $this->warn('IMAGE_UPLOAD_OPTIMIZE=false — aktifkan di .env terlebih dahulu.');

            return self::FAILURE;
        }

        $disk = (string) $this->option('disk');
        $profileKey = (string) $this->option('profile');
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $paths = $this->collectPaths($limit);
        if ($paths === []) {
            $this->info('Tidak ada path gambar di database.');

            return self::SUCCESS;
        }

        $storage = Storage::disk($disk);
        $optimized = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '' || ! $storage->exists($path)) {
                $skipped++;

                continue;
            }

            $mime = $storage->mimeType($path);
            if (! is_string($mime) || ! $optimizer->isOptimizableMime($mime)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line($path);
                $optimized++;

                continue;
            }

            try {
                $newPath = $optimizer->optimizeStoredPath($path, $disk, $profileKey);
                if ($newPath !== $path) {
                    $this->syncPathInDatabase($path, $newPath);
                }
                $optimized++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Gagal: {$path} — {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Dioptimasi: {$optimized}, dilewati: {$skipped}, gagal: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function collectPaths(int $limit): array
    {
        $paths = [];

        foreach (MuthowifProfile::query()->select(['photo_path', 'ktp_image_path'])->cursor() as $profile) {
            if (count($paths) >= $limit) {
                break;
            }
            if (filled($profile->photo_path)) {
                $paths[] = (string) $profile->photo_path;
            }
            if (filled($profile->ktp_image_path)) {
                $paths[] = (string) $profile->ktp_image_path;
            }
        }

        if (count($paths) < $limit) {
            foreach (MuthowifPortfolioImage::query()
                ->select(['path'])
                ->orderByDesc('created_at')
                ->limit($limit - count($paths))
                ->pluck('path') as $path) {
                if (count($paths) >= $limit) {
                    break;
                }
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        if (count($paths) < $limit) {
            foreach (MuthowifSupportingDocument::query()
                ->select(['path'])
                ->orderByDesc('created_at')
                ->limit($limit - count($paths))
                ->pluck('path') as $path) {
                if (count($paths) >= $limit) {
                    break;
                }
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        if (count($paths) < $limit) {
            foreach (BookingChatMessage::query()
                ->whereNotNull('image_path')
                ->select(['image_path'])
                ->orderByDesc('created_at')
                ->limit($limit - count($paths))
                ->pluck('image_path') as $path) {
                if (count($paths) >= $limit) {
                    break;
                }
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique(array_slice($paths, 0, $limit)));
    }

    private function syncPathInDatabase(string $oldPath, string $newPath): void
    {
        MuthowifProfile::query()->where('photo_path', $oldPath)->update(['photo_path' => $newPath]);
        MuthowifProfile::query()->where('ktp_image_path', $oldPath)->update(['ktp_image_path' => $newPath]);
        MuthowifPortfolioImage::query()->where('path', $oldPath)->update(['path' => $newPath]);
        MuthowifSupportingDocument::query()->where('path', $oldPath)->update(['path' => $newPath]);
        BookingChatMessage::query()->where('image_path', $oldPath)->update(['image_path' => $newPath]);
    }
}
