<div
    class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3"
    x-data="globalChatPanel({
        listUrl: @js(route('chat.conversations')),
        userId: @js(auth()->id()),
        locale: @js(str_replace('_', '-', app()->getLocale())),
        labels: {
            title: 'Inbox',
            empty: 'Belum ada obrolan aktif.',
            placeholder: 'Ketik pesan...',
            closed: 'Sesi obrolan telah ditutup.',
            loadError: 'Gagal memuat pesan.',
            sendError: 'Gagal mengirim pesan.'
        }
    })"
>
    <!-- Modal Container -->
    <div 
        x-show="isPanelExpanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        x-cloak
        class="mb-2 w-80 sm:w-96 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 shadow-2xl shadow-slate-900/20 ring-1 ring-slate-100/80 flex flex-col h-[500px]"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-4 py-3 shrink-0">
            <div class="flex items-center gap-2 overflow-hidden">
                <button type="button" x-show="view === 'chat'" x-cloak @click="closeChat()" class="text-slate-500 hover:text-slate-700 transition" aria-label="Back">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 truncate" x-text="headerTitle"></h2>
                    <p class="text-[10px] text-slate-500 truncate" x-text="headerSubtitle"></p>
                </div>
            </div>
            <button type="button" @click="togglePanel()" class="text-slate-400 hover:text-slate-600 transition shrink-0 ml-2" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- List View -->
        <div x-show="view === 'list'" class="flex-1 overflow-y-auto w-full bg-slate-50/50 p-2 space-y-1">
            <p x-show="loadingList" class="p-4 text-center text-sm text-slate-500">Memuat...</p>
            <p x-show="!loadingList && conversations.length === 0" x-cloak class="p-4 text-center text-sm text-slate-500" x-text="labels.empty"></p>
            
            <template x-for="conv in conversations" :key="conv.id">
                <button 
                    type="button" 
                    @click="openChat(conv)"
                    class="w-full flex items-center gap-3 p-3 text-left rounded-2xl hover:bg-white transition ring-1 ring-transparent hover:ring-slate-200 shadow-sm hover:shadow active:scale-[0.98]"
                >
                    <div class="h-10 w-10 rounded-full bg-slate-200 flex-shrink-0 border border-slate-200 overflow-hidden">
                        <template x-if="conv.photo_url">
                            <img :src="conv.photo_url" class="h-full w-full object-cover text-[0px]">
                        </template>
                        <template x-if="!conv.photo_url">
                            <svg class="h-full w-full text-slate-400 p-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-900 truncate" x-text="conv.other_name"></p>
                            <p class="text-[10px] text-slate-400 shrink-0" x-text="formatTimeShort(conv.last_message_time)"></p>
                        </div>
                        <p class="text-[10px] text-brand-600 font-medium truncate" x-text="`${conv.booking_code} • ${conv.service_type}`"></p>
                        <p class="text-xs text-slate-500 truncate mt-0.5" x-text="conv.last_message"></p>
                    </div>
                </button>
            </template>
        </div>

        <!-- Chat View -->
        <div x-show="view === 'chat'" x-cloak class="flex-1 flex flex-col min-h-0 bg-white">
            <div
                x-show="!activeConversation?.is_open"
                x-cloak
                class="m-3 mb-0 rounded-2xl border border-amber-200 bg-amber-50/90 px-3 py-2 text-xs font-medium text-amber-950 text-center"
            >
                <span x-text="labels.closed"></span>
            </div>

            <div
                x-ref="chatScroll"
                class="flex-1 space-y-2 overflow-y-auto px-2 py-3 sm:px-3 bg-slate-50/30"
            >
                <p x-show="loadingChat && messages.length === 0" class="px-2 py-6 text-center text-sm text-slate-500">Memuat...</p>
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
                                <img :src="m.image_url" alt="" class="max-h-40 max-w-full rounded-lg object-contain" loading="lazy">
                            </a>
                            <p class="whitespace-pre-wrap break-words text-[13px] leading-relaxed" x-show="m.body && m.body.trim()" x-text="m.body"></p>
                            <p class="mt-1 text-[9px] opacity-75" :class="m.is_me ? 'text-right': ''" x-text="formatTimeShort(m.created_at)"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Chat Input Footer -->
            <div class="border-t border-slate-100 p-3 bg-white shrink-0">
                <div x-show="imagePreviewUrl" x-cloak class="mb-2 rounded-xl border border-slate-200 bg-white p-2 flex items-start justify-between">
                    <img :src="imagePreviewUrl" alt="" class="max-h-24 max-w-full rounded-lg object-contain">
                    <button type="button" @click="clearImage()" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex items-end gap-2 text-sm">
                    <label class="cursor-pointer shrink-0 p-2 text-slate-400 hover:text-brand-600 transition" :class="(!activeConversation?.is_open || sending) ? 'opacity-50 pointer-events-none' : ''">
                        <input type="file" class="hidden" x-ref="chatImageInput" accept="image/*" @change="pickImage($event)" :disabled="!activeConversation?.is_open || sending">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    </label>
                    <textarea
                        x-model="body"
                        rows="1"
                        class="min-h-[2.5rem] w-full flex-1 rounded-2xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-400 resize-none py-2"
                        :placeholder="activeConversation?.is_open ? labels.placeholder : labels.closed"
                        :disabled="!activeConversation?.is_open || sending"
                        @keydown.enter.prevent="if(!event.shiftKey) send()"
                    ></textarea>
                    <button
                        type="button"
                        @click="send()"
                        :disabled="!activeConversation?.is_open || sending || (!body.trim() && !imageFile)"
                        class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-brand-600 w-10 h-10 text-white shadow-md transition hover:bg-brand-700 disabled:opacity-50"
                    >
                        <span x-show="!sending"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg></span>
                        <span x-show="sending" x-cloak><svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button
        type="button"
        @click="togglePanel()"
        class="relative flex h-14 w-14 lg:h-16 lg:w-16 items-center justify-center rounded-full bg-brand-600 text-white shadow-xl shadow-brand-600/40 ring-4 ring-white transition-all hover:bg-brand-700 hover:scale-105 active:scale-95 z-50 group"
        aria-label="Toggle Chat"
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
