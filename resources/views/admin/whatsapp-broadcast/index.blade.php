@php
    use App\Enums\MuthowifVerificationStatus;

    $oldProfileIds = old('muthowif_profile_ids', []);
    if (! is_array($oldProfileIds)) {
        $oldProfileIds = [];
    }

    $filterParams = function (?string $s) use ($search): array {
        $p = [];
        if ($search !== '') {
            $p['q'] = $search;
        }
        if ($s !== null && $s !== '' && $s !== 'all') {
            $p['status'] = $s;
        }

        return $p;
    };

    $muthowifPayload = $muthowifs->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->user?->name ?? '—',
        'phone' => $p->phone,
        'status' => $p->verification_status instanceof MuthowifVerificationStatus
            ? $p->verification_status->value
            : (string) $p->verification_status,
        'status_label' => $p->verification_status instanceof MuthowifVerificationStatus
            ? $p->verification_status->label()
            : (string) $p->verification_status,
    ])->values();
@endphp

<x-app-layout>
    <div
        class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12"
        x-data="whatsappBroadcastAdmin({
            muthowifs: @js($muthowifPayload),
            initialSelected: @js($oldProfileIds),
            initialFreeNumbers: @js(old('free_numbers', '')),
        })"
    >
        <x-page-container class="relative space-y-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('broadcast_failures'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm">
                    <p class="font-semibold">{{ __('admin.whatsapp_broadcast.failures_title') }}</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                        @foreach (session('broadcast_failures') as $failure)
                            <li><span class="font-medium">{{ $failure['label'] }}</span> — {{ $failure['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-emerald-900 via-brand-900 to-slate-950 p-6 text-white shadow-xl ring-1 ring-white/10 sm:rounded-3xl sm:p-8">
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-200/90">{{ __('admin.whatsapp_broadcast.badge') }}</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('admin.whatsapp_broadcast.title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/75">{{ __('admin.whatsapp_broadcast.subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        {{ __('admin.whatsapp_broadcast.back_settings') }}
                    </a>
                </div>

                @unless ($whatsappConfigured)
                    <div class="relative mt-6 rounded-2xl border border-amber-300/40 bg-amber-500/20 px-4 py-3 text-sm text-amber-50">
                        {{ __('admin.whatsapp_broadcast.token_missing') }}
                    </div>
                @endunless
                @unless ($mediaUrlPublic)
                    <div class="relative mt-6 rounded-2xl border border-sky-300/40 bg-sky-500/20 px-4 py-3 text-sm text-sky-50">
                        {{ __('admin.whatsapp_broadcast.media_url_local_hint', ['url' => $mediaBaseUrl]) }}
                    </div>
                @endunless
            </div>

            <form
                method="post"
                action="{{ route('admin.whatsapp-broadcast.send') }}"
                enctype="multipart/form-data"
                class="space-y-6"
                @submit="return confirmSend($event)"
            >
                @csrf

                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_broadcast.message_section') }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.message_hint') }}</p>
                    </div>
                    <div class="space-y-5 p-5">
                        <div>
                            <label for="broadcast-attachment" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.whatsapp_broadcast.attachment_label') }}</label>
                            <input
                                id="broadcast-attachment"
                                type="file"
                                name="attachment"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                class="mt-1.5 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
                                @change="previewAttachment($event)"
                            />
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.attachment_hint') }}</p>
                            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                            <div x-show="attachmentPreviewUrl" x-cloak class="mt-4">
                                <img :src="attachmentPreviewUrl" alt="" class="max-h-48 rounded-xl border border-slate-200 object-contain shadow-sm" />
                            </div>
                            <p x-show="attachmentFileName && !attachmentPreviewUrl" x-cloak class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <svg class="h-5 w-5 shrink-0 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                <span x-text="attachmentFileName"></span>
                            </p>
                        </div>
                        <div>
                            <label for="broadcast-message" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.whatsapp_broadcast.caption_label') }}</label>
                            <textarea
                                id="broadcast-message"
                                name="message"
                                rows="6"
                                maxlength="4000"
                                placeholder="{{ __('admin.whatsapp_broadcast.message_placeholder') }}"
                                class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >{{ old('message') }}</textarea>
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.caption_hint') }}</p>
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_broadcast.muthowif_section') }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.muthowif_hint') }}</p>
                    </div>

                    <div class="border-b border-slate-100 px-5 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="muthowif-search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.whatsapp_broadcast.search_label') }}</label>
                                <input
                                    id="muthowif-search"
                                    type="search"
                                    x-model="search"
                                    placeholder="{{ __('admin.whatsapp_broadcast.search_placeholder') }}"
                                    class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.whatsapp-broadcast.index', $filterParams('all')) }}" class="rounded-xl px-3 py-2 text-xs font-semibold {{ $status === 'all' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('admin.whatsapp_broadcast.filter_all') }} ({{ $counts['all'] }})</a>
                                <a href="{{ route('admin.whatsapp-broadcast.index', $filterParams('approved')) }}" class="rounded-xl px-3 py-2 text-xs font-semibold {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('admin.whatsapp_broadcast.filter_approved') }} ({{ $counts['approved'] }})</a>
                                <a href="{{ route('admin.whatsapp-broadcast.index', $filterParams('pending')) }}" class="rounded-xl px-3 py-2 text-xs font-semibold {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('admin.whatsapp_broadcast.filter_pending') }} ({{ $counts['pending'] }})</a>
                                <a href="{{ route('admin.whatsapp-broadcast.index', $filterParams('rejected')) }}" class="rounded-xl px-3 py-2 text-xs font-semibold {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('admin.whatsapp_broadcast.filter_rejected') }} ({{ $counts['rejected'] }})</a>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="button" @click="selectAllVisible()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('admin.whatsapp_broadcast.select_all_visible') }}
                            </button>
                            <button type="button" @click="clearSelection()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('admin.whatsapp_broadcast.clear_selection') }}
                            </button>
                            <span class="text-xs text-slate-500" x-text="selectedCountLabel()"></span>
                        </div>
                    </div>

                    <div class="max-h-[28rem] overflow-y-auto p-2">
                        <template x-if="filteredMuthowifs().length === 0">
                            <p class="px-3 py-8 text-center text-sm text-slate-500">{{ __('admin.whatsapp_broadcast.muthowif_empty') }}</p>
                        </template>
                        <ul class="divide-y divide-slate-100">
                            <template x-for="item in filteredMuthowifs()" :key="item.id">
                                <li class="flex items-start gap-3 rounded-xl px-3 py-3 transition hover:bg-slate-50/80">
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                        :value="item.id"
                                        name="muthowif_profile_ids[]"
                                        :checked="selectedIds.includes(item.id)"
                                        @change="toggleId(item.id)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900" x-text="item.name"></p>
                                        <p class="text-xs text-slate-500">
                                            <span x-text="item.phone"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="item.status_label"></span>
                                        </p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_broadcast.free_section') }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.free_hint') }}</p>
                    </div>
                    <div class="p-5">
                        <textarea
                            id="free-numbers"
                            name="free_numbers"
                            rows="5"
                            x-model="freeNumbers"
                            placeholder="{{ __('admin.whatsapp_broadcast.free_placeholder') }}"
                            class="w-full rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        ></textarea>
                        <p class="mt-2 text-xs text-slate-500" x-text="freeNumbersCountLabel()"></p>
                        <x-input-error :messages="$errors->get('recipients')" class="mt-2" />
                        <x-input-error :messages="$errors->get('muthowif_profile_ids')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900" x-text="totalRecipientsLabel()"></p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_broadcast.send_warning') }}</p>
                    </div>
                    <button
                        type="submit"
                        @disabled(!$whatsappConfigured)
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ __('admin.whatsapp_broadcast.send_button') }}
                    </button>
                </div>
            </form>
        </x-page-container>
    </div>

    @push('scripts')
        <script>
            function whatsappBroadcastAdmin(config) {
                return {
                    muthowifs: config.muthowifs ?? [],
                    selectedIds: Array.isArray(config.initialSelected) ? [...config.initialSelected] : [],
                    freeNumbers: config.initialFreeNumbers ?? '',
                    attachmentPreviewUrl: null,
                    attachmentFileName: null,
                    search: @js($search),
                    init() {
                        this.$nextTick(() => this.syncCheckboxes());
                    },
                    previewAttachment(event) {
                        const file = event.target.files?.[0];
                        if (!file) {
                            this.attachmentPreviewUrl = null;
                            this.attachmentFileName = null;
                            return;
                        }
                        this.attachmentFileName = file.name;
                        if (file.type.startsWith('image/')) {
                            this.attachmentPreviewUrl = URL.createObjectURL(file);
                        } else {
                            this.attachmentPreviewUrl = null;
                        }
                    },
                    filteredMuthowifs() {
                        const q = (this.search ?? '').trim().toLowerCase();
                        if (q === '') {
                            return this.muthowifs;
                        }
                        return this.muthowifs.filter((item) => {
                            const hay = `${item.name} ${item.phone} ${item.status_label}`.toLowerCase();
                            return hay.includes(q);
                        });
                    },
                    toggleId(id) {
                        const idx = this.selectedIds.indexOf(id);
                        if (idx === -1) {
                            this.selectedIds.push(id);
                        } else {
                            this.selectedIds.splice(idx, 1);
                        }
                    },
                    selectAllVisible() {
                        const visibleIds = this.filteredMuthowifs().map((item) => item.id);
                        visibleIds.forEach((id) => {
                            if (!this.selectedIds.includes(id)) {
                                this.selectedIds.push(id);
                            }
                        });
                        this.$nextTick(() => this.syncCheckboxes());
                    },
                    clearSelection() {
                        this.selectedIds = [];
                        this.$nextTick(() => this.syncCheckboxes());
                    },
                    syncCheckboxes() {
                        this.$el.querySelectorAll('input[name="muthowif_profile_ids[]"]').forEach((el) => {
                            el.checked = this.selectedIds.includes(el.value);
                        });
                    },
                    parseFreeNumbers() {
                        const raw = (this.freeNumbers ?? '').trim();
                        if (raw === '') {
                            return [];
                        }
                        return raw.split(/[\s,;\n\r]+/).map((v) => v.trim()).filter((v) => v !== '');
                    },
                    selectedCountLabel() {
                        const n = this.selectedIds.length;
                        return @js(__('admin.whatsapp_broadcast.selected_count')).replace(':count', String(n));
                    },
                    freeNumbersCountLabel() {
                        const n = this.parseFreeNumbers().length;
                        return @js(__('admin.whatsapp_broadcast.free_count')).replace(':count', String(n));
                    },
                    totalRecipientsLabel() {
                        const n = this.selectedIds.length + this.parseFreeNumbers().length;
                        return @js(__('admin.whatsapp_broadcast.total_estimate')).replace(':count', String(n));
                    },
                    confirmSend(event) {
                        const total = this.selectedIds.length + this.parseFreeNumbers().length;
                        if (total === 0) {
                            event.preventDefault();
                            alert(@js(__('admin.whatsapp_broadcast.recipients_required')));
                            return false;
                        }
                        const messageEl = document.getElementById('broadcast-message');
                        const attachmentEl = document.getElementById('broadcast-attachment');
                        const hasMessage = messageEl && messageEl.value.trim() !== '';
                        const hasAttachment = attachmentEl && attachmentEl.files && attachmentEl.files.length > 0;
                        if (!hasMessage && !hasAttachment) {
                            event.preventDefault();
                            alert(@js(__('admin.whatsapp_broadcast.message_or_attachment_required')));
                            return false;
                        }
                        const msg = @js(__('admin.whatsapp_broadcast.confirm_send')).replace(':count', String(total));
                        if (!window.confirm(msg)) {
                            event.preventDefault();
                            return false;
                        }
                        return true;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
