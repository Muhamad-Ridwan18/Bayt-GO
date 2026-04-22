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
        class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3"
        x-data="bookingChatPanel({
            bookingId: @js($booking->id),
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
        <div 
            x-show="isPanelExpanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            x-cloak
            class="mb-2 w-80 sm:w-96 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 shadow-2xl shadow-slate-900/20 ring-1 ring-slate-100/80"
        >
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.chat.title') }}</h2>
                    <p class="mt-0.5 text-[11px] text-slate-600" x-text="chatOpen ? introOpen : introClosed"></p>
                </div>
                <button type="button" @click="togglePanel()" class="text-slate-400 hover:text-slate-600 transition" aria-label="Close Chat">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
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
                    x-ref="chatScroll"
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
                                <a
                                    x-show="m.image_url"
                                    x-cloak
                                    :href="m.image_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-1 block outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                                >
                                    <img :src="m.image_url" alt="" class="max-h-52 max-w-full rounded-lg object-contain" loading="lazy" width="400" height="400">
                                </a>
                                <p class="mt-0.5 whitespace-pre-wrap break-words text-[13px] leading-relaxed" x-show="m.body && m.body.trim()" x-text="m.body"></p>
                                <p class="mt-1 text-[9px] opacity-75" x-text="formatTime(m.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <input
                        type="file"
                        x-ref="chatImageInput"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-800 hover:file:bg-slate-200 disabled:opacity-50"
                        :disabled="!chatOpen || sending"
                        @change="pickImage($event)"
                    >
                    <button
                        type="button"
                        x-show="imagePreviewUrl"
                        x-cloak
                        @click="clearImage()"
                        class="text-xs font-semibold text-slate-600 underline underline-offset-2 hover:text-slate-900"
                    >
                        {{ __('bookings.chat.remove_image') }}
                    </button>
                </div>
                <div x-show="imagePreviewUrl" x-cloak class="mt-2 rounded-xl border border-slate-200 bg-white p-2">
                    <img :src="imagePreviewUrl" alt="" class="max-h-32 max-w-full rounded-lg object-contain">
                </div>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="sr-only" for="booking-chat-body-{{ $booking->getKey() }}">{{ __('bookings.chat.placeholder') }}</label>
                    <textarea
                        id="booking-chat-body-{{ $booking->getKey() }}"
                        x-model="body"
                        rows="1"
                        maxlength="4000"
                        :disabled="!chatOpen || sending"
                        :placeholder="chatOpen ? @js(__('bookings.chat.placeholder')) : @js(__('bookings.chat.closed_banner'))"
                        class="min-h-[2.5rem] w-full flex-1 rounded-2xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 resize-none py-2"
                        @keydown.enter.prevent="if(!event.shiftKey) send()"
                    ></textarea>
                    <button
                        type="button"
                        @click="send()"
                        :disabled="!chatOpen || sending || (!body.trim() && !imageFile)"
                        class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 h-10"
                        aria-label="{{ __('bookings.chat.send') }}"
                    >
                        <span x-show="!sending">
                            <svg class="h-4 w-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </span>
                        <span x-show="sending" x-cloak>
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <button
            type="button"
            @click="togglePanel()"
            class="relative flex h-14 w-14 lg:h-16 lg:w-16 items-center justify-center rounded-full bg-brand-600 text-white shadow-xl shadow-brand-600/40 ring-4 ring-white transition-all hover:bg-brand-700 hover:scale-105 active:scale-95 z-50 group"
            aria-label="{{ __('bookings.chat.title') }}"
        >
            <span x-show="hasUnread" x-cloak class="absolute top-0 right-0 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 ring-2 ring-white"></span>
            </span>
            <svg x-show="!isPanelExpanded" class="h-7 w-7 transition-transform group-hover:-rotate-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            <svg x-show="isPanelExpanded" x-cloak class="h-6 w-6 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
@endif
