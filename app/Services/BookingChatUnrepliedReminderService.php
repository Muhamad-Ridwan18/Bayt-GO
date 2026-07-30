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

            $oldestUnread = $this->oldestUnreadCustomerMessage($booking);
            if ($oldestUnread === null || $oldestUnread->created_at->gt($threshold)) {
                continue;
            }

            $recipient = $this->muthowifRecipient($booking);
            if ($recipient === null) {
                continue;
            }

            $sendType = $this->resolveSendType($booking, $oldestUnread, $today, $force);
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
                    $this->markSent($booking, $oldestUnread, $today, $sendType);
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

    private function oldestUnreadCustomerMessage(MuthowifBooking $booking): ?BookingChatMessage
    {
        return $booking->chatMessages()
            ->where('user_id', $booking->customer_id)
            ->whereNull('read_at')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * @return array{role: string, user: User, dial: array{target: string, country_calling_code: string}}|null
     */
    private function muthowifRecipient(MuthowifBooking $booking): ?array
    {
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

    public static function resetReminders(?string $bookingRef = null): int
    {
        $bookingId = self::resolveBookingId($bookingRef);
        $cleared = 0;

        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\RedisStore) {
            $redis = $store->connection();
            $prefix = $store->getPrefix();
            $pattern = $prefix.self::CACHE_PREFIX.'*';
            if ($bookingId !== null) {
                $pattern = $prefix.self::CACHE_PREFIX.$bookingId.'*';
            }

            $firstPattern = $prefix.self::FIRST_CACHE_PREFIX.'*';
            if ($bookingId !== null) {
                $firstPattern = $prefix.self::FIRST_CACHE_PREFIX.$bookingId.':*';
            }

            foreach ([$pattern, $firstPattern] as $searchPattern) {
                $keys = $redis->keys($searchPattern);
                foreach ($keys as $key) {
                    if ($redis->del($key) > 0) {
                        $cleared++;
                    }
                }
            }

            return $cleared;
        }

        $query = \Illuminate\Support\Facades\DB::table(config('cache.stores.database.table', 'cache'))
            ->where(static function ($q): void {
                $q->where('key', 'like', '%'.self::CACHE_PREFIX.'%')
                    ->orWhere('key', 'like', '%'.self::FIRST_CACHE_PREFIX.'%');
            });

        if ($bookingId !== null) {
            $query->where(static function ($q) use ($bookingId): void {
                $q->where('key', 'like', '%'.self::CACHE_PREFIX.$bookingId.'%')
                    ->orWhere('key', 'like', '%'.self::FIRST_CACHE_PREFIX.$bookingId.':%');
            });
        }

        $cleared = (int) $query->delete();

        if ($cleared === 0 && $bookingRef === null) {
            return self::resetRemindersByBookingIteration($bookingId);
        }

        return $cleared;
    }

    private static function resetRemindersByBookingIteration(?string $bookingId): int
    {
        $cleared = 0;
        $query = MuthowifBooking::query()->whereHas('chatMessages');
        if ($bookingId !== null) {
            $query->whereKey($bookingId);
        }

        foreach ($query->cursor() as $booking) {
            $unreadMessages = $booking->chatMessages()
                ->where('user_id', $booking->customer_id)
                ->whereNull('read_at')
                ->get(['id']);

            foreach ($unreadMessages as $message) {
                if (Cache::forget(self::firstCacheKey($booking, $message))) {
                    $cleared++;
                }
            }

            for ($day = 0; $day < 14; $day++) {
                $date = now()->subDays($day)->toDateString();
                if (Cache::forget(self::dailyCacheKey($booking, $date))) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }

    private static function resolveBookingId(?string $bookingRef): ?string
    {
        if ($bookingRef === null || trim($bookingRef) === '') {
            return null;
        }

        $bookingRef = trim($bookingRef);

        $byCode = MuthowifBooking::query()
            ->where('booking_code', $bookingRef)
            ->value('id');

        if ($byCode !== null) {
            return (string) $byCode;
        }

        if (MuthowifBooking::query()->whereKey($bookingRef)->exists()) {
            return $bookingRef;
        }

        return null;
    }
}
