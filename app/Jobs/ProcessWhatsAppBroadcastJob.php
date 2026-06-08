<?php

namespace App\Jobs;

use App\Services\WhatsAppBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    /**
     * @param  list<string>  $muthowifProfileIds
     */
    public function __construct(
        public string $message,
        public array $muthowifProfileIds,
        public string $freeNumbersText,
        public ?string $attachmentLocalPath = null,
        public ?string $attachmentFilename = null,
        public ?string $attachmentPublicUrl = null,
    ) {}

    public function handle(WhatsAppBroadcastService $broadcast): void
    {
        $result = $broadcast->send(
            $this->message,
            $this->muthowifProfileIds,
            $this->freeNumbersText,
            $this->attachmentLocalPath,
            $this->attachmentFilename,
            $this->attachmentPublicUrl,
        );

        Log::info('whatsapp.broadcast.completed', [
            'queued' => $result['sent'],
            'skipped' => $result['skipped'],
        ]);
    }
}
