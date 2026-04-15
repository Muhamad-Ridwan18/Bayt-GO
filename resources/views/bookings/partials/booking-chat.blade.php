@props([
    'booking',
    'fetchUrl',
    'storeUrl',
])

@php
    use App\Enums\BookingStatus;

    $initialOpen = $booking->isBookingChatOpen();
    $showPanel = $initialOpen || ($booking->status === BookingStatus::Completed && $booking->isPaid());
@endphp

@if ($showPanel)
    <div
        class="mt-8 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 shadow-md shadow-slate-900/5 ring-1 ring-slate-100/80"
        x-data="bookingChatPanel({
            fetchUrl: @js($fetchUrl),
            storeUrl: @js($storeUrl),
            initialOpen: @js($initialOpen),
            locale: @js(str_replace('_', '-', app()->getLocale())),
            introOpen: @js(__('bookings.chat.intro_open')),
            introClosed: @js(__('bookings.chat.intro_closed')),
            labels: {
                loadError: @js(__('bookings.chat.load_error')),
                sendError: @js(__('bookings.chat.send_error')),
            },
        })"
    >
        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.chat.title') }}</h2>
            <p class="mt-1 text-sm text-slate-600" x-text="chatOpen ? introOpen : introClosed"></p>
        </div>

        <div class="px-3 py-3 sm:px-5 sm:py-4">
            <div
                x-show="!chatOpen"
                x-cloak
                class="mb-3 rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm font-medium text-amber-950"
                role="status"
            >
                {{ __('bookings.chat.closed_banner') }}
            </div>

            <p x-show="error" x-text="error" class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" x-cloak></p>

            <div
                class="max-h-80 space-y-2 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50/50 px-2 py-3 sm:px-3"
                aria-live="polite"
            >
                <p x-show="loading && messages.length === 0" class="px-2 py-6 text-center text-sm text-slate-500">{{ __('bookings.chat.loading') }}</p>
                <template x-if="!loading && messages.length === 0">
                    <p class="px-2 py-6 text-center text-sm text-slate-500">{{ __('bookings.chat.empty') }}</p>
                </template>
                <template x-for="m in messages" :key="m.id">
                    <div class="flex px-1" :class="m.is_me ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm sm:max-w-[75%]"
                            :class="m.is_me
                                ? 'rounded-br-md bg-brand-600 text-white'
                                : 'rounded-bl-md border border-slate-200 bg-white text-slate-800'"
                        >
                            <p class="text-[11px] font-semibold opacity-90" x-text="m.sender_name"></p>
                            <p class="mt-0.5 whitespace-pre-wrap break-words" x-text="m.body"></p>
                            <p class="mt-1 text-[10px] opacity-75" x-text="formatTime(m.created_at)"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                <label class="sr-only" for="booking-chat-body-{{ $booking->getKey() }}">{{ __('bookings.chat.placeholder') }}</label>
                <textarea
                    id="booking-chat-body-{{ $booking->getKey() }}"
                    x-model="body"
                    rows="2"
                    maxlength="4000"
                    :disabled="!chatOpen || sending"
                    :placeholder="chatOpen ? @js(__('bookings.chat.placeholder')) : @js(__('bookings.chat.closed_banner'))"
                    class="min-h-[2.75rem] w-full flex-1 rounded-2xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
                ></textarea>
                <button
                    type="button"
                    @click="send()"
                    :disabled="!chatOpen || sending || !body.trim()"
                    class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-show="!sending">{{ __('bookings.chat.send') }}</span>
                    <span x-show="sending" x-cloak>{{ __('bookings.chat.sending') }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
