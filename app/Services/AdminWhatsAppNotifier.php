<?php

namespace App\Services;

use App\Jobs\SendWhatsAppTextJob;
use App\Models\MuthowifProfile;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AdminWhatsAppNotifier
{
    public function notifyMuthowifRegistrationSubmitted(MuthowifProfile $profile): void
    {
        try {
            if (! config('services.fonnte.muthowif_registration_admin_notify_enabled', true)) {
                return;
            }

            $numbers = config('emergency.admin_whatsapp_numbers', []);
            if (! is_array($numbers) || $numbers === []) {
                return;
            }

            $token = config('services.fonnte.token');
            if ($token === null || $token === '') {
                Log::debug('WhatsApp muthowif registration admin notify skipped: FONNTE_TOKEN kosong.');

                return;
            }

            $profile->loadMissing(['user', 'referredBy.user']);

            $locale = config('app.locale');
            $message = $this->withLocale($locale, function () use ($profile, $locale): string {
                $user = $profile->user;
                $appName = config('app.name', 'BaytGo');
                $url = route('admin.muthowif.show', $profile);

                $lines = [
                    __('whatsapp.admin.muthowif_registration_submitted.headline', ['app' => $appName], $locale),
                    '',
                    __('whatsapp.admin.muthowif_registration_submitted.body', [
                        'name' => $user?->name ?? '—',
                    ], $locale),
                    '',
                    __('whatsapp.admin.muthowif_registration_submitted.email', ['email' => $user?->email ?? '—'], $locale),
                    __('whatsapp.admin.muthowif_registration_submitted.phone', ['phone' => $profile->phone ?? '—'], $locale),
                ];

                $inviterName = $profile->referredBy?->user?->name;
                if (filled($inviterName)) {
                    $lines[] = __('whatsapp.admin.muthowif_registration_submitted.inviter', ['inviter' => $inviterName], $locale);
                }

                $lines[] = '';
                $lines[] = __('whatsapp.admin.muthowif_registration_submitted.open', [], $locale);
                $lines[] = $url;

                return implode("\n", $lines);
            });

            $this->sendToPhoneNumbers($numbers, $message, (string) $profile->getKey());
        } catch (Throwable $e) {
            Log::warning('WhatsApp muthowif registration admin notify failed', [
                'profile_id' => $profile->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<string>  $phones
     */
    private function sendToPhoneNumbers(array $phones, string $message, string $contextId): void
    {
        foreach ($phones as $index => $phone) {
            $dial = IntlPhone::fonnteDial((string) $phone);
            if ($dial === null) {
                Log::warning('WhatsApp admin notify skipped: nomor tidak valid.', [
                    'phone' => $phone,
                    'context_id' => $contextId,
                ]);

                continue;
            }

            $job = SendWhatsAppTextJob::dispatch(
                $dial['target'],
                $message,
                $dial['country_calling_code'],
            );

            if ($index > 0) {
                $job->delay(now()->addMilliseconds($index * 300));
            }
        }
    }

    /**
     * @param  callable():string  $callback
     */
    private function withLocale(string $locale, callable $callback): string
    {
        $previous = app()->getLocale();
        try {
            app()->setLocale($locale);

            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }
}
