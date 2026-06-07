<?php

namespace App\Services;

use App\Jobs\SendWhatsAppTextJob;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifProfile;
use App\Support\IndonesianNumber;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AdminWhatsAppNotifier
{
    public function notifyRefundRequestSubmitted(BookingRefundRequest $refund): void
    {
        try {
            if (! config('services.fonnte.refund_admin_notify_enabled', true)) {
                return;
            }

            $numbers = config('emergency.admin_whatsapp_numbers', []);
            if (! is_array($numbers) || $numbers === []) {
                return;
            }

            $token = config('services.fonnte.token');
            if ($token === null || $token === '') {
                Log::debug('WhatsApp refund admin notify skipped: FONNTE_TOKEN kosong.');

                return;
            }

            $refund->loadMissing([
                'customer',
                'muthowifBooking.customer',
                'muthowifBooking.muthowifProfile.user',
            ]);

            $booking = $refund->muthowifBooking;
            if ($booking === null) {
                return;
            }

            $locale = config('app.locale');
            $message = $this->withLocale($locale, function () use ($refund, $booking, $locale): string {
                $customerName = $refund->customer?->name
                    ?? $booking->customer?->name
                    ?? __('whatsapp.fallback_pilgrim', [], $locale);
                $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);
                $start = $booking->starts_on->format('d/m/Y');
                $end = $booking->ends_on->format('d/m/Y');
                $amountFmt = IndonesianNumber::formatThousands((string) (int) round((float) $refund->net_refund_customer));
                $appName = config('app.name', 'BaytGo');
                $url = route('admin.refunds.index');

                $lines = [
                    __('whatsapp.admin.refund_request_submitted.headline', ['app' => $appName], $locale),
                    '',
                    __('whatsapp.admin.refund_request_submitted.body', ['customer' => $customerName], $locale),
                    '',
                ];

                if (filled($booking->booking_code)) {
                    $lines[] = __('whatsapp.admin.refund_request_submitted.booking_code', ['code' => $booking->booking_code], $locale);
                }

                $lines[] = __('whatsapp.admin.refund_request_submitted.muthowif', ['muthowif' => $muthowifName], $locale);
                $lines[] = __('whatsapp.admin.refund_request_submitted.service_dates', ['start' => $start, 'end' => $end], $locale);
                $lines[] = __('whatsapp.admin.refund_request_submitted.net_refund', ['amount' => $amountFmt], $locale);
                $lines[] = __('whatsapp.admin.refund_request_submitted.bank', [
                    'bank' => $refund->refund_bank_name,
                    'holder' => $refund->refund_account_holder,
                    'number' => $refund->refund_account_number,
                ], $locale);

                if (filled($refund->customer_note)) {
                    $lines[] = '';
                    $lines[] = __('whatsapp.admin.refund_request_submitted.note_heading', [], $locale);
                    $lines[] = $refund->customer_note;
                }

                $lines[] = '';
                $lines[] = __('whatsapp.admin.refund_request_submitted.open', [], $locale);
                $lines[] = $url;

                return implode("\n", $lines);
            });

            $this->sendToPhoneNumbers($numbers, $message, (string) $refund->getKey());
        } catch (Throwable $e) {
            Log::warning('WhatsApp refund admin notify failed', [
                'refund_id' => $refund->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

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
