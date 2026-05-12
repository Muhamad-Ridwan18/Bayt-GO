@php
    use App\Enums\MuthowifServiceType;
    use Carbon\Carbon;

    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);

    $searchRangeLabel = null;
    if ($startDate !== '') {
        try {
            $endEff = $endDate !== '' ? $endDate : $startDate;
            $searchRangeLabel = Carbon::parse($startDate)->format('d/m/Y').' – '.Carbon::parse($endEff)->format('d/m/Y');
        } catch (\Throwable) {
            $searchRangeLabel = null;
        }
    }

    $listQs = array_filter([
        'q' => request()->query('q'),
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'service_type' => is_string(request()->query('service_type')) && in_array(request()->query('service_type'), ['group', 'private'], true)
            ? request()->query('service_type')
            : null,
        'pilgrim_count' => is_numeric(request()->query('pilgrim_count')) && (int) request()->query('pilgrim_count') > 0
            ? (string) (int) request()->query('pilgrim_count')
            : null,
    ], fn ($v) => filled($v));
    $profileUrl = route('layanan.show', array_merge(['publicProfile' => $profile], $listQs));

    $indexedUrl = route('layanan.index', array_filter([
        'q' => request()->query('q'),
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
    ], fn ($v) => filled($v)));
@endphp

<x-marketplace-layout :title="__('layanan.book_document_title', ['name' => $profile->user->name])" wide>
    <div class="relative min-w-0 space-y-6 overflow-x-hidden">
        <div class="pointer-events-none absolute -left-24 top-0 h-64 w-64 rounded-full bg-brand-200/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 top-24 h-56 w-56 rounded-full bg-amber-200/15 blur-3xl" aria-hidden="true"></div>

        <nav aria-label="{{ __('layanan.book_breadcrumb_aria') }}" class="relative flex flex-wrap items-center gap-x-2 gap-y-1 rounded-2xl border border-slate-200/80 bg-white/95 px-3 py-2.5 text-sm shadow-sm ring-1 ring-slate-100/80 sm:gap-x-2.5 sm:px-4">
            <a href="{{ $indexedUrl }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-800">
                <svg class="h-4 w-4 shrink-0 opacity-80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                </svg>
                {{ __('layanan.breadcrumb_find') }}
            </a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <a href="{{ $profileUrl }}" class="min-w-0 truncate font-semibold text-slate-700 decoration-slate-300 decoration-2 underline-offset-2 hover:text-brand-800 hover:decoration-brand-400">{{ $profile->user->name }}</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <span class="font-bold text-slate-900">{{ __('layanan.book_breadcrumb_here') }}</span>
        </nav>

        @if (($bookingIntent['can_submit'] ?? false) && ($group || $private))
            <div class="relative rounded-2xl border border-emerald-200/85 bg-gradient-to-br from-emerald-50/95 via-white to-white px-4 py-3 shadow-sm ring-1 ring-emerald-100/70 sm:px-5 sm:py-3.5" role="status">
                <p class="text-sm font-bold text-emerald-950">{{ __('layanan.book_banner_title') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-emerald-900/90">{{ __('layanan.book_banner_sub') }}</p>
            </div>
        @endif

        <div
            class="grid min-w-0 grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(17.5rem,22rem)] lg:items-start lg:gap-x-10 lg:gap-y-8 xl:grid-cols-[minmax(0,1fr)_minmax(19rem,24rem)]"
        >
            <div class="min-w-0 lg:col-start-1 lg:row-start-1 lg:space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan.book_main_kicker') }}</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('marketplace.panel.title') }}</h1>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base">{{ __('marketplace.panel.subtitle') }}</p>
                <a href="{{ $profileUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                    {{ __('layanan.book_back_profile') }}
                </a>
            </div>

            <div class="min-w-0 lg:sticky lg:top-24 lg:z-10 lg:col-start-2 lg:row-start-1 lg:row-span-2 lg:self-start">
                @include('layanan.partials.booking-sidebar', [
                    'profile' => $profile,
                    'searchRangeLabel' => $searchRangeLabel,
                    'bookingIntent' => $bookingIntent,
                ])
            </div>

            <div id="booking-box" class="min-w-0 scroll-mt-24 lg:col-start-1 lg:row-start-2">
                @include('layanan.partials.booking-panel', [
                    'profile' => $profile,
                    'group' => $group,
                    'private' => $private,
                    'bookingIntent' => $bookingIntent,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ])
            </div>
        </div>
    </div>
</x-marketplace-layout>
