@php
    use App\Enums\BookingStatus;

    $statusFilter = $statusFilter ?? null;
    $bookingStatusCounts = $bookingStatusCounts ?? [];
    $indexRoute = $indexRoute ?? 'bookings.index';
    $filterAllLabel = $filterAllLabel ?? __('bookings.index_page.filter_all');
    $totalAll = array_sum($bookingStatusCounts);
@endphp
<nav
    data-live-part="tabs"
    class="mt-2 -mb-2 overflow-x-auto pb-1"
    aria-label="{{ __('bookings.index_page.tabs_aria') }}"
>
    <div class="inline-flex min-w-full gap-2 sm:min-w-0">
        <a
            href="{{ route($indexRoute) }}"
            class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $statusFilter === null ? 'bg-slate-900 text-white shadow-md shadow-slate-900/20' : 'bg-white text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-50' }}"
        >
            <span>{{ $filterAllLabel }}</span>
            <span class="tabular-nums text-xs opacity-80">{{ $totalAll }}</span>
        </a>
        @foreach (BookingStatus::cases() as $status)
            @php
                $count = (int) ($bookingStatusCounts[$status->value] ?? 0);
                $active = $statusFilter === $status->value;
            @endphp
            <a
                href="{{ route($indexRoute, ['status' => $status->value]) }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-brand-600 text-white shadow-md shadow-brand-600/25' : 'bg-white text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-50' }}"
            >
                <span>{{ $status->label() }}</span>
                <span class="tabular-nums text-xs {{ $active ? 'text-white/85' : 'text-slate-500' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>
</nav>
