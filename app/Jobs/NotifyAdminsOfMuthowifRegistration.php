<?php

namespace App\Jobs;

use App\Models\MuthowifProfile;
use App\Services\AdminWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAdminsOfMuthowifRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hanya sekali — notifikasi opsional, tidak perlu retry berulang. */
    public int $tries = 1;

    public function __construct(
        public string $profileId,
    ) {}

    /**
     * Antrikan notifikasi tanpa mengganggu alur pendaftaran jika antrian gagal.
     */
    public static function afterMuthowifRegistered(?string $profileId): void
    {
        if ($profileId === null || $profileId === '') {
            return;
        }

        try {
            self::dispatchAfterResponse($profileId);
        } catch (Throwable $e) {
            Log::warning('muthowif_registration_admin_notify_dispatch_failed', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handle(AdminWhatsAppNotifier $notifier): void
    {
        try {
            $profile = MuthowifProfile::query()->find($this->profileId);
            if ($profile) {
                $notifier->notifyMuthowifRegistrationSubmitted($profile);
            }
        } catch (Throwable $e) {
            Log::warning('muthowif_registration_admin_notify_failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
