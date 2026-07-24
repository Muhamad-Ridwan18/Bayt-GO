<?php

namespace App\Jobs;

use App\Services\WhatsAppBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppBroadcastJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public int $uniqueFor = 600;

    /**
     * @param  list<string>  $muthowifProfileIds
     */
    public function __construct(
        public string $message,
        public array $muthowifProfileIds,
        public string $freeNumbersText,
        public ?string $attachmentPublicUrl = null,
        public string $idempotencyKey = '',
    ) {}

    public function uniqueId(): string
    {
        return $this->idempotencyKey !== ''
            ? 'wa-broadcast:'.$this->idempotencyKey
            : 'wa-broadcast:'.hash('sha256', json_encode([
                $this->message,
                $this->muthowifProfileIds,
                $this->freeNumbersText,
                $this->attachmentPublicUrl,
            ], JSON_THROW_ON_ERROR));
    }

    public function handle(WhatsAppBroadcastService $broadcast): void
    {
        $result = $broadcast->send(
            $this->message,
            $this->muthowifProfileIds,
            $this->freeNumbersText,
            $this->attachmentPublicUrl,
            $this->idempotencyKey !== '' ? $this->idempotencyKey : null,
        );

        Log::info('whatsapp.broadcast.completed', [
            'idempotency_key' => $this->idempotencyKey,
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
        ]);
    }
}
