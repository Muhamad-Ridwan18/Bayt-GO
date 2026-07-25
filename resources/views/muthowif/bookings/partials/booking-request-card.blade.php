@php
    use App\Support\IndonesianNumber;
    use Illuminate\Support\Str;

    /** @var \App\ViewModels\Booking\MuthowifBookingIndexCardData $card */
    $booking = $card->booking;
    $st = $booking->status;
    $customerInitial = Str::upper(Str::substr($card->customerName, 0, 1));
    $dateRangePretty = $booking->starts_on->translatedFormat('d M Y').' – '.$booking->ends_on->translatedFormat('d M Y');
@endphp

<li
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80"
    x-data="{
        open: @js($errors->has('muthowif_rejection_kind') || $errors->has('muthowif_rejection_note') || filled($card->rejectNoteOld)),
        showBreakdown: true,
        showAllDocs: false,
        rejectNote: @js($card->rejectNoteOld),
        rejectNoteLen: @js(strlen($card->rejectNoteOld)),
        docModalOpen: false,
        docTitle: '',
        docPreviewUrl: '',
        docKind: 'image',
        openDocPreview(title, url, kind) {
            this.docTitle = title;
            this.docPreviewUrl = url;
            this.docKind = kind;
            this.docModalOpen = true;
            document.body.classList.add('overflow-y-hidden');
        },
        closeDocPreview() {
            this.docModalOpen = false;
            document.body.classList.remove('overflow-y-hidden');
        },
        openReject() {
            this.open = true;
            this.$nextTick(() => {
                this.$refs.rejectPanel?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        },
    }"
    @keydown.escape.window="docModalOpen && closeDocPreview()"
>
    <div class="min-w-0">
            {{-- ===== Mobile summary (mockup) ===== --}}
            <div class="sm:hidden">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-baytgo text-sm font-bold text-white" aria-hidden="true">{{ $customerInitial }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-bold text-slate-900">{{ $card->customerName }}</p>
                                    @if (filled($booking->booking_code))
                                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking->booking_code }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $card->badgeClass }}">
                                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                                    {{ $st->label() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="flex items-start gap-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-baytgo" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold leading-snug text-slate-900">{{ $dateRangePretty }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ __('muthowif.bookings.card_meta_date') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-baytgo" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.956 9.956 0 017 18a9.956 9.956 0 01-5.385-1.572zM16.5 18c1.042 0 2.024-.193 2.929-.54a1.2 1.2 0 00.571-1.15 4.001 4.001 0 00-7.753-1.377c.426.65.67 1.42.67 2.246 0 .264-.022.523-.064.773A9.97 9.97 0 0016.5 18z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold leading-snug text-slate-900">{{ __('muthowif.bookings.pilgrim_count_label', ['count' => $booking->pilgrim_count]) }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ __('muthowif.bookings.card_meta_pilgrims') }}</p>
                            </div>
                        </div>
                        <div class="col-span-2 flex items-start gap-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-baytgo" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.288-.15.715-.369 1.245-.667 1.032-.6 2.405-1.474 3.79-2.65 1.385-1.176 2.618-2.54 3.39-3.96a10.78 10.78 0 002.133-5.85V6.75A2.25 2.25 0 0013.5 4.5h-7A2.25 2.25 0 004.5 6.75v.823c.001 1.812.317 3.569.92 5.176 1.003 2.63 2.79 4.893 4.87 6.174zM10 10.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold leading-snug text-slate-900">{{ $booking->service_type?->label() ?? '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ __('muthowif.bookings.card_meta_service') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3">
                    <p class="text-sm font-medium text-slate-600">{{ __('muthowif.bookings.net_earning_short') }}</p>
                    <p class="text-lg font-bold tabular-nums text-baytgo">Rp {{ $card->muthowifNetFormatted }}</p>
                </div>

                <div class="grid grid-cols-3 gap-2 border-t border-slate-100 px-4 py-3">
                    <a
                        href="{{ route('muthowif.bookings.show', $booking) }}"
                        class="inline-flex items-center justify-center gap-1 rounded-xl border border-baytgo/30 bg-white px-2 py-2.5 text-xs font-semibold text-baytgo"
                    >
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                        {{ __('muthowif.bookings.view_detail') }}
                    </a>
                    @if ($card->isPending)
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-red-200 bg-red-50 px-2 py-2.5 text-xs font-semibold text-red-700"
                            @click="openReject()"
                        >
                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            {{ __('muthowif.bookings.reject') }}
                        </button>
                        <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            <button type="submit" :disabled="submitting" class="inline-flex h-full w-full items-center justify-center gap-1 rounded-xl bg-baytgo px-2 py-2.5 text-xs font-semibold text-white disabled:opacity-70">
                                <svg x-show="!submitting" class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.approve'))"></span>
                            </button>
                        </form>
                    @else
                        <button
                            type="button"
                            class="col-span-2 inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-2 py-2.5 text-xs font-semibold text-slate-700"
                            @click="open = !open"
                        >
                            <span x-text="open ? @js(__('muthowif.bookings.hide_breakdown')) : @js(__('muthowif.bookings.view_breakdown'))"></span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- ===== Desktop header ===== --}}
            <div class="hidden p-5 sm:block">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:gap-6">
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 flex-col gap-3 text-left sm:flex-row sm:items-center sm:gap-4"
                        @click="open = !open"
                        :aria-expanded="open"
                    >
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-baytgo text-sm font-bold text-white" aria-hidden="true">{{ $customerInitial }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-base font-semibold text-slate-900">{{ $card->customerName }}</p>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $card->badgeClass }}">
                                        {{ $st->label() }}
                                    </span>
                                </div>
                                @if (filled($booking->booking_code))
                                    <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking->booking_code }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600">
                                    <span>{{ $dateRangePretty }}</span>
                                    <span>{{ __('muthowif.bookings.pilgrims_meta', ['count' => $booking->pilgrim_count]) }}</span>
                                    <span>{{ $booking->service_type?->label() ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </button>

                    <div class="flex shrink-0 flex-col items-end gap-1 sm:min-w-[9rem]" x-show="!open">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('muthowif.bookings.net_earning_short') }}</p>
                        <p class="text-xl font-bold tabular-nums text-emerald-700 sm:text-2xl">Rp {{ $card->muthowifNetFormatted }}</p>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2 self-end xl:self-center">
                        <div class="flex items-center gap-2" x-show="!open" x-cloak>
                            <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
                                {{ __('muthowif.bookings.view_detail') }}
                            </a>
                            @if ($card->isPending)
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" @click="openReject()">
                                    {{ __('muthowif.bookings.reject') }}
                                </button>
                                <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" x-data="{ submitting: false }" @submit="submitting = true">
                                    @csrf
                                    <button type="submit" :disabled="submitting" class="inline-flex items-center gap-1.5 rounded-lg bg-baytgo px-3 py-2 text-sm font-semibold text-white hover:bg-baytgo-800 disabled:opacity-70">
                                        <span x-text="submitting ? @js(__('muthowif.bookings.submitting')) : @js(__('muthowif.bookings.approve'))"></span>
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="flex items-center gap-2" x-show="open" x-cloak>
                            <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm" @click.stop>
                                {{ __('muthowif.bookings.view_detail_btn') }}
                            </a>
                            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800" @click.stop="$dispatch('open-booking-chat', { bookingId: @js($booking->getKey()) })">
                                {{ __('muthowif.bookings.send_message_btn') }}
                            </button>
                            @if ($card->canCancelUnpaid)
                                <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="inline" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));" @click.stop>
                                    @csrf
                                    <x-submit-button class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                        {{ __('muthowif.bookings.cancel') }}
                                    </x-submit-button>
                                </form>
                            @endif
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" @click="open = !open" :aria-expanded="open">
                            <svg class="h-5 w-5 transition" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-cloak
                class="border-t border-slate-100"
            >
                <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-2 lg:items-stretch lg:gap-5">
                    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 3.5A1.5 1.5 0 013.5 2h2.879a1.5 1.5 0 011.06.44l1.122 1.12a1.5 1.5 0 001.06.44H16.5A1.5 1.5 0 0118 5.5v9a1.5 1.5 0 01-1.5 1.5h-13A1.5 1.5 0 012 14.5v-11z" /></svg>
                            </span>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.service_breakdown_heading') }}</h3>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                        <div x-show="showBreakdown" class="space-y-0 divide-y divide-slate-100 text-sm">
                            <div class="flex justify-between gap-2 py-2">
                                <span class="text-slate-600">{{ __('muthowif.booking_show.subtotal_service') }}</span>
                                <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($card->serviceSubtotal)) }}</span>
                            </div>
                            @foreach ($card->addonLines as $ad)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ $ad->name }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round((float) $ad->price)) }}</span>
                                </div>
                            @endforeach
                            @if ($card->sameHotelLine > 0)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ __('bookings.show.same_hotel_label', ['nights' => $card->nights, 'days' => __('common.days')]) }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($card->sameHotelLine)) }}</span>
                                </div>
                            @endif
                            @if ($card->transportLine > 0)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ __('bookings.show.transport_label') }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($card->transportLine)) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between gap-2 py-2">
                                <span class="text-red-600">{{ __('muthowif.bookings.platform_fee_pct_label', ['pct' => $card->platformPct]) }}</span>
                                <span class="font-medium tabular-nums text-red-600">- Rp {{ IndonesianNumber::formatThousands((string) (int) round($card->muthowifFee)) }}</span>
                            </div>
                        </div>
                        <button type="button" class="mt-3 text-left text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showBreakdown = !showBreakdown">
                            <span x-text="showBreakdown ? @js(__('muthowif.bookings.hide_breakdown')) : @js(__('muthowif.bookings.view_breakdown'))"></span>
                        </button>
                        </div>
                        <div class="border-t border-emerald-100 bg-emerald-50/90 px-4 py-3">
                            <p class="text-[11px] font-medium text-emerald-800/90">{{ __('muthowif.bookings.estimated_net_earning') }}</p>
                            <p class="mt-0.5 text-lg font-bold tabular-nums text-emerald-700">Rp {{ $card->muthowifNetFormatted }}</p>
                        </div>
                    </div>

                    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06L11.939 3.44A1.5 1.5 0 0010.939 3H4.5zm2 1.5h6.439l3.122 3.12A.5.5 0 0116 7.121V16.5a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5V4.5a.5.5 0 01.5-.5z" clip-rule="evenodd" /></svg>
                            </span>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.travel_documents_heading') }}</h3>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                        <div class="min-h-[4rem] flex-1">
                            @if ($card->hasDocuments)
                                @include('bookings.partials.booking-documents', [
                                    'booking' => $booking,
                                    'routeName' => 'muthowif.bookings.documents.show',
                                    'variant' => 'list',
                                    'collapseLimit' => 3,
                                    'actionStyle' => 'pill',
                                ])
                            @else
                                <p class="text-sm text-slate-500">{{ __('muthowif.bookings.no_documents') }}</p>
                            @endif
                        </div>
                        @if ($card->hasDocuments && $card->documentCount > 3)
                            <button type="button" class="mt-auto inline-flex items-center gap-1 pt-3 text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showAllDocs = !showAllDocs">
                                <span x-text="showAllDocs ? @js(__('muthowif.bookings.hide_documents')) : @js(__('muthowif.bookings.view_all_documents_count', ['count' => $card->documentCount]))"></span>
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </button>
                        @endif
                        </div>
                    </div>
                </div>

                @if ($card->isPending)
                    <div x-ref="rejectPanel">
                        @include('muthowif.bookings.partials.pending-booking-actions', ['booking' => $booking, 'variant' => 'card'])
                    </div>
                @elseif ($card->hasPendingReschedule)
                    <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-200/90">
                            {{ __('muthowif.bookings.reschedule_badge') }}
                        </a>
                    </div>
                @endif
            </div>
    </div>

    @include('bookings.partials.booking-documents-preview-modal')
</li>
