<?php

namespace App\Jobs;

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
        public string $message = '',
        public ?string $countryCallingCode = null,
        public array $failureCacheKeysToForget = [],
        public ?string $overrideGatewayToken = null,
        public ?string $overrideGatewayApiUrl = null,
        public ?string $overrideGatewaySessionId = null,
        public ?string $overrideGatewayCountryCode = null,
        public bool $rethrowOnFailure = false,
        public ?string $messageCacheKey = null,
    ) {}

    /**
     * Kirim teks sensitif (OTP): isi pesan di cache, job hanya menyimpan key opaq.
     *
     * @param  list<string>  $failureCacheKeysToForget
     */
    public static function dispatchCachedAfterResponse(
        string $target,
        string $message,
        ?string $countryCallingCode = null,
        array $failureCacheKeysToForget = [],
    ): void {
        $key = 'wa_outbound:'.bin2hex(random_bytes(16));
        Cache::put($key, $message, now()->addMinutes(10));

        $forget = array_values(array_filter(
            [...$failureCacheKeysToForget, $key],
            static fn (string $k): bool => $k !== ''
        ));

        self::dispatchAfterResponse(
            $target,
            '',
            $countryCallingCode,
            $forget,
            null,
            null,
            null,
            null,
            false,
            $key,
        );
    }

    public function handle(FonnteService $fonnte): void
    {
        try {
            $message = $this->resolveMessage();

            if ($this->overrideGatewayToken !== null) {
                $fonnte->sendTextWithGateway(
                    $this->overrideGatewayToken,
                    $this->overrideGatewayApiUrl ?? '',
                    $this->overrideGatewaySessionId,
                    $this->overrideGatewayCountryCode ?? '62',
                    $this->target,
                    $message,
                    $this->countryCallingCode,
                );
            } else {
                $fonnte->sendText($this->target, $message, $this->countryCallingCode);
            }
        } catch (RuntimeException|Throwable $e) {
            foreach ($this->failureCacheKeysToForget as $cacheKey) {
                if ($cacheKey !== '') {
                    Cache::forget($cacheKey);
                }
            }

            Log::warning('whatsapp.job_failed', [
                'target' => $this->target,
                'exception' => $e->getMessage(),
            ]);

            if ($this->rethrowOnFailure) {
                throw $e;
            }
        }
    }

    private function resolveMessage(): string
    {
        if ($this->messageCacheKey !== null && $this->messageCacheKey !== '') {
            $cached = Cache::pull($this->messageCacheKey);
            if (! is_string($cached) || $cached === '') {
                throw new RuntimeException('Pesan WhatsApp kedaluwarsa atau tidak ditemukan.');
            }

            return $cached;
        }

        return $this->message;
    }
}
