<x-app-layout>
    <div
        class="mx-auto w-full px-3 py-4 sm:px-6 sm:py-6 lg:px-8 xl:px-10"
        x-data="globalChatPanel({
            pageMode: true,
            openBookingId: @js($openBookingId),
            listUrl: @js(route('chat.conversations')),
            userId: @js(auth()->id()),
            locale: @js(str_replace('_', '-', app()->getLocale())),
            labels: {
                title: @js(__('dashboard.customer_chat_title')),
                empty: @js(__('dashboard.customer_chat_empty_body')),
                placeholder: @js(__('bookings.chat.placeholder')),
                closed: @js(__('bookings.chat.closed_banner')),
                loadError: @js(__('bookings.chat.load_error')),
                sendError: @js(__('bookings.chat.send_error')),
            }
        })"
    >
        <div class="mb-4 hidden sm:mb-5 lg:block">
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ __('dashboard.customer_chat_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('dashboard.customer_chat_sub') }}</p>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
            <div class="grid min-h-[min(70dvh,640px)] lg:min-h-[min(75dvh,720px)] lg:grid-cols-[minmax(16rem,22rem)_minmax(0,1fr)]">
                {{-- Conversation list --}}
                <aside
                    class="flex min-h-0 flex-col border-slate-100 bg-slate-50/60 lg:border-r"
                    :class="view === 'chat' ? 'hidden lg:flex' : 'flex'"
                >
                    <div class="shrink-0 border-b border-slate-100 bg-white px-4 py-3 lg:hidden">
                        <p class="text-sm font-bold text-slate-900">{{ __('dashboard.customer_chat_title') }}</p>
                        <p class="text-[11px] text-slate-500">{{ __('dashboard.customer_chat_sub') }}</p>
                    </div>

                    <div class="flex-1 space-y-1 overflow-y-auto p-2">
                        <p x-show="loadingList" class="p-4 text-center text-sm text-slate-500">{{ __('bookings.chat.loading') }}</p>

                        <div x-show="!loadingList && conversations.length === 0" x-cloak class="px-4 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-baytgo">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.245-.986 2.31-2.236 2.436-.348.036-.695.06-1.043.079v3.24a.75.75 0 01-1.28.53l-3.244-3.243a8.955 8.955 0 01-1.236.084 8.91 8.91 0 01-5.033-1.55 8.91 8.91 0 01-2.915-3.33A8.91 8.91 0 013.75 9.75c0-1.63.437-3.157 1.2-4.47A8.955 8.955 0 019.75 2.25c2.485 0 4.71 1.006 6.33 2.63"/>
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-800">{{ __('dashboard.customer_chat_empty_title') }}</p>
                            <p class="mt-1 text-xs text-slate-500" x-text="labels.empty"></p>
                            <a href="{{ route('bookings.index') }}" class="mt-4 inline-flex rounded-xl bg-baytgo px-4 py-2.5 text-xs font-semibold text-white">{{ __('dashboard.customer_chat_empty_cta') }}</a>
                        </div>

                        <template x-for="conv in conversations" :key="conv.id">
                            <button
                                type="button"
                                @click="openChat(conv)"
                                class="flex w-full items-center gap-3 rounded-2xl p-3 text-left transition ring-1"
                                :class="activeConversation && String(activeConversation.id) === String(conv.id)
                                    ? 'bg-white shadow-sm ring-baytgo/20'
                                    : 'ring-transparent hover:bg-white hover:ring-slate-200'"
                            >
                                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-200">
                                    <template x-if="conv.photo_url">
                                        <img :src="conv.photo_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!conv.photo_url">
                                        <svg class="h-full w-full p-2.5 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-bold text-slate-900" x-text="conv.other_name"></p>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <span
                                                x-show="(conv.unread_count || 0) > 0"
                                                x-cloak
                                                class="inline-flex min-w-[1.125rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white"
                                                x-text="(conv.unread_count || 0) > 99 ? '99+' : conv.unread_count"
                                            ></span>
                                            <p class="text-[10px] text-slate-400" x-text="formatTimeShort(conv.last_message_time)"></p>
                                        </div>
                                    </div>
                                    <p class="truncate text-[10px] font-medium text-baytgo" x-text="`${conv.booking_code} · ${conv.service_type}`"></p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500" x-text="conv.last_message"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </aside>

                {{-- Chat room --}}
                <section
                    class="flex min-h-0 min-w-0 flex-col bg-white"
                    :class="view === 'chat' ? 'flex' : 'hidden lg:flex'"
                >
                    <template x-if="view !== 'chat' || !activeConversation">
                        <div class="hidden flex-1 flex-col items-center justify-center px-6 py-16 text-center lg:flex">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-800">{{ __('dashboard.customer_chat_pick_title') }}</p>
                            <p class="mt-1 max-w-xs text-xs text-slate-500">{{ __('dashboard.customer_chat_pick_body') }}</p>
                        </div>
                    </template>

                    <template x-if="view === 'chat' && activeConversation">
                        <div class="flex h-full min-h-0 flex-col">
                            <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-3 sm:px-4">
                                <button type="button" @click="closeChat()" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-white hover:text-slate-800 lg:hidden" aria-label="Back">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <div class="h-9 w-9 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-200">
                                    <template x-if="activeConversation.photo_url">
                                        <img :src="activeConversation.photo_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!activeConversation.photo_url">
                                        <svg class="h-full w-full p-2 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="truncate text-sm font-bold text-slate-900" x-text="headerTitle"></h2>
                                    <p class="truncate text-[10px] text-slate-500" x-text="headerSubtitle"></p>
                                </div>
                            </div>

                            <div
                                x-show="!activeConversation.is_open"
                                x-cloak
                                class="mx-3 mt-3 shrink-0 rounded-2xl border border-amber-200 bg-amber-50/90 px-3 py-2 text-center text-xs font-medium text-amber-950 sm:mx-4"
                                x-text="labels.closed"
                            ></div>

                            <div
                                x-ref="chatScroll"
                                class="flex-1 space-y-2 overflow-y-auto bg-slate-50/40 px-3 py-3 sm:px-4"
                            >
                                <p x-show="loadingChat && messages.length === 0" class="px-2 py-8 text-center text-sm text-slate-500">{{ __('bookings.chat.loading') }}</p>
                                <p x-show="!loadingChat && messages.length === 0" x-cloak class="px-2 py-8 text-center text-sm text-slate-500">{{ __('bookings.chat.empty') }}</p>
                                <p x-show="error" x-cloak class="px-2 py-2 text-center text-xs text-red-600" x-text="error"></p>

                                <template x-for="m in messages" :key="m.id">
                                    <div class="flex px-1" :class="m.is_me ? 'justify-end' : 'justify-start'">
                                        <div
                                            class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm"
                                            :class="m.is_me
                                                ? 'rounded-br-md bg-brand-600 text-white'
                                                : 'rounded-bl-md border border-slate-200 bg-white text-slate-800'"
                                        >
                                            <a
                                                x-show="m.image_url"
                                                x-cloak
                                                :href="m.image_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-1 block outline-none"
                                            >
                                                <img :src="m.image_url" alt="" class="max-h-48 max-w-full rounded-lg object-contain" loading="lazy">
                                            </a>
                                            <p class="whitespace-pre-wrap break-words text-[13px] leading-relaxed" x-show="m.body && m.body.trim()" x-text="m.body"></p>
                                            <div class="mt-1 flex items-center gap-1" :class="m.is_me ? 'justify-end' : ''">
                                                <p class="text-[9px] opacity-75" x-text="formatTimeShort(m.created_at)"></p>
                                                <span
                                                    x-show="m.is_me"
                                                    x-cloak
                                                    class="inline-flex shrink-0 items-center"
                                                    :class="m.is_read ? 'text-[#53BDEB]' : 'text-white/55'"
                                                    aria-hidden="true"
                                                >
                                                    <svg class="h-[14px] w-[18px]" viewBox="0 0 18 11" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 5.5l3.5 3.5L9.5 1.5"/>
                                                        <path d="M5.5 5.5L9 9l7-7.5"/>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="shrink-0 border-t border-slate-100 bg-white p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:p-4">
                                <div x-show="imagePreviewUrl" x-cloak class="mb-2 flex items-start justify-between rounded-xl border border-slate-200 bg-white p-2">
                                    <img :src="imagePreviewUrl" alt="" class="max-h-24 max-w-full rounded-lg object-contain">
                                    <button type="button" @click="clearImage()" class="p-1 text-slate-400 hover:text-slate-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="flex items-end gap-2 text-sm">
                                    <label class="shrink-0 cursor-pointer p-2 text-slate-400 transition hover:text-brand-600" :class="(!activeConversation?.is_open || sending) ? 'pointer-events-none opacity-50' : ''">
                                        <input type="file" class="hidden" x-ref="chatImageInput" accept="image/*" @change="pickImage($event)" :disabled="!activeConversation?.is_open || sending">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    </label>
                                    <textarea
                                        x-model="body"
                                        rows="1"
                                        class="min-h-[2.5rem] w-full flex-1 resize-none rounded-2xl border-slate-300 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-400"
                                        :placeholder="activeConversation?.is_open ? labels.placeholder : labels.closed"
                                        :disabled="!activeConversation?.is_open || sending"
                                        @keydown.enter.prevent="if(!event.shiftKey) send()"
                                    ></textarea>
                                    <button
                                        type="button"
                                        @click="send()"
                                        :disabled="!activeConversation?.is_open || sending || (!body.trim() && !imageFile)"
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-md transition hover:bg-brand-700 disabled:opacity-50"
                                    >
                                        <span x-show="!sending"><svg class="h-4 w-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg></span>
                                        <span x-show="sending" x-cloak><svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
