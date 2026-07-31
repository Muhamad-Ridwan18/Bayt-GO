<?php

namespace App\Jobs;

use App\Services\MailjetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendMailjetTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<array{ContentType: string, Filename: string, Base64Content: string}>  $attachments
     */
    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $message,
        public bool $rethrowOnFailure = false,
        public array $attachments = [],
    ) {}

    public function handle(MailjetService $mailjet): void
    {
        try {
            $mailjet->sendText(
                $this->toEmail,
                $this->subject,
                $this->message,
                null,
                null,
                $this->attachments,
            );
        } catch (RuntimeException|Throwable $e) {
            Log::warning('mailjet.job_failed', [
                'to' => $this->toEmail,
                'exception' => $e->getMessage(),
            ]);

            if ($this->rethrowOnFailure) {
                throw $e;
            }
        }
    }
}
