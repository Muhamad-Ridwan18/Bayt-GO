@php
    use App\Support\IndonesianNumber;

    /** @var \App\ViewModels\Booking\MuthowifBookingIndexCardData $card */
    $booking = $card->booking;
    $st = $booking->status;
@endphp

<li
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80"
    x-data="{
        open: false,
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
    }"
    @keydown.escape.window="docModalOpen && closeDocPreview()"
>
    <div class="min-w-0">
            {{-- Header --}}
            <div class="flex flex-col gap-4 p-4 sm:p-5 xl:flex-row xl:items-center xl:gap-6">
                <button
                    type="button"
                    class="flex min-w-0 flex-1 flex-col gap-3 text-left sm:flex-row sm:items-center sm:gap-4"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 ring-1 ring-slate-200/80" aria-hidden="true">
                            @if ($card->isCompany)
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.16 3.5a1.25 1.25 0 011.24-1.07l.04-.01h8.32c.62 0 1.17.37 1.4.94l2.24 5.54a1.25 1.25 0 01-1.2 1.72H4.16a1.25 1.25 0 01-1.24-1.44l.04-.18 1.2-5.5zM6 8.75a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" />
                                </svg>
                            @endif
                        </span>
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
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                    {{ $card->dateRangeLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                                    {{ __('muthowif.bookings.pilgrims_meta', ['count' => $booking->pilgrim_count]) }}
                                </span>
                                <span>{{ $booking->service_type?->label() ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </button>

                <div class="flex shrink-0 flex-col items-end gap-1 sm:min-w-[9rem] xl:items-center xl:text-center" x-show="!open">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('muthowif.bookings.net_earning_short') }}</p>
                    <p class="text-xl font-bold tabular-nums text-emerald-700 sm:text-2xl">Rp {{ $card->muthowifNetFormatted }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2 self-end xl:self-center">
                    <div class="hidden items-center gap-2 sm:flex" x-show="open" x-cloak>
                        <a
                            href="{{ route('muthowif.bookings.show', $booking) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            @click.stop
                        >
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                            {{ __('muthowif.bookings.view_detail_btn') }}
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            @click.stop="$dispatch('open-booking-chat', { bookingId: @js($booking->getKey()) })"
                        >
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3.505 2.365A41.369 41.369 0 0110 2c2.89 0 5.66.7 8.03 1.96a.75.75 0 01.47.69v9.02a.75.75 0 01-.94.72C16.2 13.35 13.18 12.5 10 12.5c-3.18 0-6.2.85-8.56 2.27a.75.75 0 01-.94-.72V3.055a.75.75 0 01.47-.69zM10 4c-2.3 0-4.5.47-6.53 1.33v7.34C5.5 11.53 7.7 11 10 11s4.5.53 6.53 1.67V5.33C14.5 4.47 12.3 4 10 4z" /></svg>
                            {{ __('muthowif.bookings.send_message_btn') }}
                        </button>
                        @if ($card->canCancelUnpaid)
                            <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="inline" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));" @click.stop>
                                @csrf
                                <x-submit-button class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 00-.53 1.28l1.5 1.5a.75.75 0 001.28-.53l-.03-.18c.716-.12 1.44-.22 2.17-.3V6A1.25 1.25 0 0110 7.25h.5A1.25 1.25 0 0111.75 6v-.75c.716.08 1.424.18 2.12.3l-.03.18a.75.75 0 001.28.53l1.5-1.5a.75.75 0 00-.53-1.28 14.85 14.85 0 00-2.365-.298V3.75A2.75 2.75 0 0011.25 1h-2.5zM4.5 8.25a.75.75 0 00-.75.75v7.5c0 .414.336.75.75.75h11a.75.75 0 00.75-.75v-7.5a.75.75 0 00-.75-.75h-11z" clip-rule="evenodd" /></svg>
                                    {{ __('muthowif.bookings.cancel') }}
                                </x-submit-button>
                            </form>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                        @click="open = !open"
                        :aria-expanded="open"
                    >
                        <svg class="h-5 w-5 transition" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </button>
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

                <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:hidden">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                            {{ __('muthowif.bookings.view_detail_btn') }}
                        </a>
                        <button type="button" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800" @click="$dispatch('open-booking-chat', { bookingId: @js($booking->getKey()) })">
                            {{ __('muthowif.bookings.send_message_btn') }}
                        </button>
                    </div>
                </div>

                @if ($card->isPending)
                    @include('muthowif.bookings.partials.pending-booking-actions', ['booking' => $booking, 'variant' => 'card'])
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
