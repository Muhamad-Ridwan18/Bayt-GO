@php
    use App\Enums\AffiliateWithdrawalStatus;
    use App\Support\IndonesianNumber;

    $tab = $tab ?? 'muthowif';
    $affiliatePendingCount = (int) ($affiliatePendingCount ?? 0);
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ __('admin.withdrawals.list_title') }}</h1>
        <p class="mt-1 text-sm text-slate-600">Kelola permintaan withdraw muthowif dan affiliate.</p>
    </div>
</div>

<div class="flex flex-wrap gap-2 border-b border-slate-200">
    <a
        href="{{ route('admin.withdrawals.index') }}"
        @class([
            'inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-semibold transition',
            'border-brand-600 text-brand-800' => $tab === 'muthowif',
            'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' => $tab !== 'muthowif',
        ])
    >
        Muthowif
    </a>
    <a
        href="{{ route('admin.withdrawals.index', ['tab' => 'affiliate']) }}"
        @class([
            'inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-semibold transition',
            'border-brand-600 text-brand-800' => $tab === 'affiliate',
            'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' => $tab !== 'affiliate',
        ])
    >
        Affiliate
        @if ($affiliatePendingCount > 0)
            <span class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-600 px-1.5 text-[10px] font-bold leading-none text-white">
                {{ $affiliatePendingCount }}
            </span>
        @endif
    </a>
</div>

@if ($tab === 'affiliate')
    @include('admin.withdrawals._affiliate_table')
@else
    <div
        x-data="adminWithdrawalsLive({
            fragmentUrl: '{{ route('admin.withdrawals.fragment') }}',
            toastLabel: '{{ __('admin.withdrawals.new_request_toast') }}'
        })"
        x-ref="liveRoot"
    >
        @include('admin.withdrawals._table')
    </div>
@endif
