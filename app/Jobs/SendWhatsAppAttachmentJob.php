<?php

namespace App\Jobs;

use App\Services\FonnteService;
use App\Support\WhatsAppMediaUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendWhatsAppAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>  $failureCacheKeysToForget
     */
    public function __construct(
        public string $target,
        public string $message,
        public ?string $countryCallingCode = null,
        public ?string $publicFileUrl = null,
        public ?string $filename = null,
        public ?string $localFilePath = null,
        public array $failureCacheKeysToForget = [],
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        try {
            if (
                $this->publicFileUrl !== null
                && $this->publicFileUrl !== ''
                && WhatsAppMediaUrl::isPubliclyReachable($this->publicFileUrl)
            ) {
                $fonnte->sendMessageWithPublicFileUrl(
                    $this->target,
                    $this->message,
                    $this->publicFileUrl,
                    $this->documentFilename(),
                    $this->countryCallingCode,
                );

                return;
            }

            if ($this->localFilePath !== null && $this->localFilePath !== '' && is_readable($this->localFilePath)) {
                $fileContents = file_get_contents($this->localFilePath);
                if ($fileContents === false || $fileContents === '') {
                    throw new RuntimeException('Berkas lampiran kosong atau tidak dapat dibaca.');
                }

                $fileName = $this->filename !== null && $this->filename !== ''
                    ? $this->filename
                    : basename($this->localFilePath);

                $this->sendWithUploadFallback($fonnte, $fileContents, $fileName);

                return;
            }

            throw new RuntimeException('Lampiran WhatsApp tidak valid.');
        } catch (RuntimeException|Throwable $e) {
            foreach ($this->failureCacheKeysToForget as $cacheKey) {
                if ($cacheKey !== '') {
                    Cache::forget($cacheKey);
                }
            }

            Log::warning('whatsapp.attachment_job_failed', [
                'target' => $this->target,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function sendWithUploadFallback(FonnteService $fonnte, string $fileContents, string $fileName): void
    {
        try {
            $fonnte->sendMessageWithFileUpload(
                $this->target,
                $this->message,
                $fileContents,
                $fileName,
                $this->countryCallingCode,
            );
        } catch (RuntimeException $uploadError) {
            if (
                $this->publicFileUrl !== null
                && $this->publicFileUrl !== ''
                && WhatsAppMediaUrl::isPubliclyReachable($this->publicFileUrl)
            ) {
                $fonnte->sendMessageWithPublicFileUrl(
                    $this->target,
                    $this->message,
                    $this->publicFileUrl,
                    $this->documentFilename(),
                    $this->countryCallingCode,
                );

                return;
            }

            throw $uploadError;
        }
    }

    private function documentFilename(): ?string
    {
        if ($this->filename === null || $this->filename === '') {
            return null;
        }

        $lower = strtolower($this->filename);

        return str_ends_with($lower, '.pdf') ? $this->filename : null;
    }
}
