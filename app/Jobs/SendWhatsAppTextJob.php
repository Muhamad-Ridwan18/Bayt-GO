<?php

namespace App\Jobs;

use App\Enums\WhatsAppGateway;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendWhatsAppTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>  $failureCacheKeysToForget
     */
    public function __construct(
        public string $target,
        public string $message,
        public ?string $countryCallingCode = null,
        public array $failureCacheKeysToForget = [],
        public WhatsAppGateway $gateway = WhatsAppGateway::Transactional,
        public ?string $overrideGatewayToken = null,
        public ?string $overrideGatewayApiUrl = null,
        public ?string $overrideGatewaySessionId = null,
        public ?string $overrideGatewayCountryCode = null,
        public bool $rethrowOnFailure = false,
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        try {
            if ($this->overrideGatewayToken !== null) {
                $fonnte->sendTextWithGateway(
                    $this->overrideGatewayToken,
                    $this->overrideGatewayApiUrl ?? '',
                    $this->overrideGatewaySessionId,
                    $this->overrideGatewayCountryCode ?? '62',
                    $this->target,
                    $this->message,
                    $this->countryCallingCode,
                );
            } else {
                $fonnte->sendText($this->target, $this->message, $this->countryCallingCode, $this->gateway);
            }
        } catch (RuntimeException|Throwable $e) {
            foreach ($this->failureCacheKeysToForget as $cacheKey) {
                if ($cacheKey !== '') {
                    Cache::forget($cacheKey);
                }
            }

            Log::warning('whatsapp.job_failed', [
                'target' => $this->target,
                'gateway' => $this->gateway->value,
                'exception' => $e->getMessage(),
            ]);

            if ($this->rethrowOnFailure) {
                throw $e;
            }
        }
    }
}
