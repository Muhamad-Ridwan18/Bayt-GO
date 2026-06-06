<?php

namespace App\Jobs;

use App\Models\BookingChatMessage;
use App\Services\UploadedImageOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OptimizeBookingChatImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public string $messageId) {}

    public function handle(UploadedImageOptimizer $optimizer): void
    {
        $message = BookingChatMessage::query()->find($this->messageId);
        if ($message === null || ! filled($message->image_path)) {
            return;
        }

        $disk = Storage::disk('local');
        $originalPath = $message->image_path;

        if (! $disk->exists($originalPath)) {
            return;
        }

        if (! $optimizer->enabled()) {
            return;
        }

        try {
            $optimizedPath = $optimizer->optimizeStoredPath($originalPath, 'local', 'chat');
        } catch (\Throwable $e) {
            Log::warning('chat.image.optimize_failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($optimizedPath === $originalPath) {
            return;
        }

        $message->update(['image_path' => $optimizedPath]);

        if ($disk->exists($originalPath)) {
            $disk->delete($originalPath);
        }
    }
}
