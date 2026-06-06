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

    $canSubmit = ($bookingIntent['can_submit'] ?? false) && ($group || $private);
@endphp

<x-marketplace-layout :title="__('layanan.book_document_title', ['name' => $profile->user->name])" wide>
    <div class="ui-booking-page">
        <nav aria-label="{{ __('layanan.book_breadcrumb_aria') }}" class="ui-toolbar text-sm">
            <a href="{{ $indexedUrl }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('layanan.breadcrumb_find') }}</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <a href="{{ $profileUrl }}" class="max-w-[12rem] truncate font-medium text-slate-700 hover:text-brand-800 sm:max-w-xs">{{ $profile->user->name }}</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <span class="font-bold text-slate-900">{{ __('layanan.book_breadcrumb_here') }}</span>
        </nav>

        <div id="booking-box" class="min-w-0 scroll-mt-24">
            @include('layanan.partials.booking-panel', [
                'profile' => $profile,
                'group' => $group,
                'private' => $private,
                'bookingIntent' => $bookingIntent,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'profileUrl' => $profileUrl,
                'searchRangeLabel' => $searchRangeLabel,
                'indexedUrl' => $indexedUrl,
                'canSubmit' => $canSubmit,
            ])
        </div>

        @include('layanan.partials.book-sticky-cta', [
            'profile' => $profile,
            'canSubmit' => $canSubmit,
            'searchRangeLabel' => $searchRangeLabel,
        ])
    </div>
</x-marketplace-layout>
