@php
    use App\Enums\BookingStatus;
@endphp

<div data-live-part="stats">
    @if ($bookings->total() > 0)
        <div class="flex flex-wrap items-center gap-2 pt-1">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-md shadow-slate-900/20">
                <span class="tabular-nums">{{ __('bookings.index_page.bookings_total', ['count' => $bookings->total()]) }}</span>
            </span>
            <div class="flex flex-wrap gap-2" role="status" aria-label="{{ __('bookings.index_page.stats_aria') }}">
                @foreach (BookingStatus::cases() as $bs)
                    @if (($bookingStatusCounts[$bs->value] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200/80">
                            <span class="max-w-[10rem] truncate">{{ $bs->label() }}</span>
                            <span class="ml-1.5 tabular-nums text-slate-500">{{ $bookingStatusCounts[$bs->value] }}</span>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
