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
use Throwable;

final class BookingChatUnrepliedReminderService
{
    private const CACHE_PREFIX = 'chat_unreplied_wa:';

    private const FIRST_CACHE_PREFIX = 'chat_unreplied_wa:first:';

    /**
     * @return array{count: int, recipients: list<array{booking_id: string, role: string, name: string, phone: string, booking_code: string, status: string, send_type?: string, error?: string}>}
     */
    public function process(bool $force = false, bool $dryRun = false): array
    {
        $forTesting = $force || $dryRun;

        if (! $forTesting && ! WhatsAppNotifySettings::enabled('chat_unreplied_daily')) {
            return ['count' => 0, 'recipients' => []];
        }

        if (! WhatsAppNotifySettings::hasToken()) {
            Log::debug('WhatsApp chat unreplied reminder skipped: token gateway kosong.');

            return ['count' => 0, 'recipients' => []];
        }

        $threshold = now()->subMinutes(WhatsAppNotifySettings::chatUnrepliedThresholdMinutes());
        $today = now()->toDateString();
        $recipients = [];

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

            $sendType = $this->resolveSendType($booking, $lastMessage, $today, $force);
            if ($sendType === null) {
                continue;
            }

            $summary = array_merge(
                $this->recipientSummary($booking, $recipient),
                ['send_type' => $sendType],
            );

            if ($dryRun) {
                $recipients[] = array_merge($summary, ['status' => 'eligible']);

                continue;
            }

            try {
                $this->sendReminder($booking, $recipient);
                if (! $force) {
                    $this->markSent($booking, $lastMessage, $today, $sendType);
                }
                $recipients[] = array_merge($summary, ['status' => 'sent']);
            } catch (Throwable $e) {
                Log::warning('chat_unreplied.wa_failed', [
                    'booking_code' => $summary['booking_code'],
                    'phone' => $summary['phone'],
                    'send_type' => $sendType,
                    'exception' => $e->getMessage(),
                ]);
                $recipients[] = array_merge($summary, [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'count' => count(array_filter(
                $recipients,
                static fn (array $row): bool => in_array($row['status'], ['sent', 'eligible'], true),
            )),
            'recipients' => $recipients,
        ];
    }

    private function resolveSendType(
        MuthowifBooking $booking,
        BookingChatMessage $lastMessage,
        string $today,
        bool $force,
    ): ?string {
        $firstKey = self::firstCacheKey($booking, $lastMessage);
        $dailyKey = self::dailyCacheKey($booking, $today);

        if (! Cache::has($firstKey)) {
            return 'first';
        }

        if (! self::isDailyScheduleWindow()) {
            return null;
        }

        if (! $force && Cache::has($dailyKey)) {
            return null;
        }

        return 'daily';
    }

    private function markSent(
        MuthowifBooking $booking,
        BookingChatMessage $lastMessage,
        string $today,
        string $sendType,
    ): void {
        $firstKey = self::firstCacheKey($booking, $lastMessage);

        if ($sendType === 'first') {
            Cache::put($firstKey, true, now()->addDays(60));
            Cache::put(self::dailyCacheKey($booking, $today), true, now()->endOfDay());

            return;
        }

        Cache::put(self::dailyCacheKey($booking, $today), true, now()->endOfDay());
    }

    private static function isDailyScheduleWindow(): bool
    {
        $scheduled = WhatsAppNotifySettings::chatUnrepliedDailyTime();
        if (! preg_match('/^(\d{2}):(\d{2})$/', $scheduled, $matches)) {
            return false;
        }

        $target = now()->copy()->setTime((int) $matches[1], (int) $matches[2], 0);
        $now = now();

        return $now->gte($target) && $now->lt($target->copy()->addMinutes(5));
    }

    /**
     * @return array{role: string, user: User, dial: array{target: string, country_calling_code: string}}|null
     */
    private function recipientFor(MuthowifBooking $booking, BookingChatMessage $lastMessage): ?array
    {
        $customerId = (string) $booking->customer_id;
        $senderId = (string) $lastMessage->user_id;

        if ($senderId !== $customerId) {
            return null;
        }

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

    /**
     * @param  array{role: string, user: User, dial: array{target: string, country_calling_code: string}}  $recipient
     * @return array{booking_id: string, role: string, name: string, phone: string, booking_code: string}
     */
    private function recipientSummary(MuthowifBooking $booking, array $recipient): array
    {
        return [
            'booking_id' => (string) $booking->getKey(),
            'role' => $recipient['role'],
            'name' => $recipient['user']->name ?? '—',
            'phone' => $recipient['dial']['target'],
            'booking_code' => $booking->booking_code ?? '—',
        ];
    }

    private static function firstCacheKey(MuthowifBooking $booking, BookingChatMessage $lastMessage): string
    {
        return self::FIRST_CACHE_PREFIX.(string) $booking->getKey().':'.(string) $lastMessage->getKey();
    }

    private static function dailyCacheKey(MuthowifBooking $booking, string $date): string
    {
        return self::CACHE_PREFIX.(string) $booking->getKey().':'.$date;
    }

    /**
     * @param  array{role: string, user: User, dial: array{target: string, country_calling_code: string}}  $recipient
     *
     * @throws Throwable
     */
    private function sendReminder(MuthowifBooking $booking, array $recipient): void
    {
        $locale = filled($recipient['user']->locale)
            ? (string) $recipient['user']->locale
            : config('app.locale');

        $name = $recipient['user']->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
        $code = $booking->booking_code ?? '—';

        SendWhatsAppTextJob::dispatchSync(
            $recipient['dial']['target'],
            self::buildMessage($name, $code, $locale),
            $recipient['dial']['country_calling_code'],
            [],
            null,
            null,
            null,
            null,
            true,
        );
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
