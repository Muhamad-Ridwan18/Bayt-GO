<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendWhatsAppTextJob;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Models\User;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class BookingChatUnrepliedReminderService
{
    private const CACHE_PREFIX = 'chat_unreplied_wa:';

    public function process(bool $force = false, bool $dryRun = false): int
    {
        $forTesting = $force || $dryRun;

        if (! $forTesting && ! WhatsAppNotifySettings::enabled('chat_unreplied_daily')) {
            return 0;
        }

        if (! WhatsAppNotifySettings::hasToken()) {
            Log::debug('WhatsApp chat unreplied reminder skipped: token gateway kosong.');

            return 0;
        }

        $threshold = now()->subMinutes(WhatsAppNotifySettings::chatUnrepliedThresholdMinutes());
        $today = now()->toDateString();
        $sent = 0;

        $bookings = MuthowifBooking::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::InProgress])
            ->whereHas('chatMessages')
            ->with(['muthowifProfile.user', 'customer'])
            ->cursor();

        foreach ($bookings as $booking) {
            if (! $booking->isBookingChatOpen()) {
                continue;
            }

            $lastMessage = $booking->chatMessages()->latest('created_at')->first();
            if ($lastMessage === null || $lastMessage->created_at->gt($threshold)) {
                continue;
            }

            $recipient = $this->recipientFor($booking, $lastMessage);
            if ($recipient === null) {
                continue;
            }

            $cacheKey = self::CACHE_PREFIX.$booking->getKey().':'.$recipient['role'].':'.$today;
            if (! $force && Cache::has($cacheKey)) {
                continue;
            }

            if ($dryRun) {
                $sent++;

                continue;
            }

            if ($this->sendReminder($booking, $recipient)) {
                if (! $force) {
                    Cache::put($cacheKey, true, now()->endOfDay());
                }
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return array{role: string, user: User, dial: array{target: string, country_calling_code: string}}|null
     */
    private function recipientFor(MuthowifBooking $booking, BookingChatMessage $lastMessage): ?array
    {
        $customerId = (string) $booking->customer_id;
        $muthowifUserId = (string) ($booking->muthowifProfile?->user_id ?? '');
        $senderId = (string) $lastMessage->user_id;

        if ($senderId === $customerId) {
            $user = $booking->muthowifProfile?->user;
            $phone = $booking->muthowifProfile?->whatsAppPhone();
            if ($user === null || $phone === null) {
                return null;
            }

            $dial = IntlPhone::fonnteDial($phone);
            if ($dial === null) {
                return null;
            }

            return ['role' => 'muthowif', 'user' => $user, 'dial' => $dial];
        }

        if ($senderId === $muthowifUserId) {
            $user = $booking->customer;
            if ($user === null) {
                return null;
            }

            $dial = IntlPhone::fonnteDial($user->phone);
            if ($dial === null) {
                return null;
            }

            return ['role' => 'customer', 'user' => $user, 'dial' => $dial];
        }

        return null;
    }

    /**
     * @param  array{role: string, user: User, dial: array{target: string, country_calling_code: string}}  $recipient
     */
    private function sendReminder(MuthowifBooking $booking, array $recipient): bool
    {
        $locale = filled($recipient['user']->locale)
            ? (string) $recipient['user']->locale
            : config('app.locale');

        $name = $recipient['user']->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
        $code = $booking->booking_code ?? '—';

        SendWhatsAppTextJob::dispatch(
            $recipient['dial']['target'],
            self::buildMessage($name, $code, $locale),
            $recipient['dial']['country_calling_code'],
        );

        return true;
    }

    public static function buildMessage(string $name, string $bookingCode, ?string $locale = null): string
    {
        $locale = $locale ?? config('app.locale');

        return implode("\n\n", [
            __('whatsapp.chat_unreplied.greeting', ['name' => $name], $locale),
            __('whatsapp.chat_unreplied.body', ['code' => $bookingCode], $locale),
            __('whatsapp.chat_unreplied.cta', [], $locale),
        ]);
    }
}
