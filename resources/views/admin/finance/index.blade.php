@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
@endphp

<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">
                    {{ __('admin.finance.intro', ['pct' => \App\Support\PlatformFee::TOTAL_RATE * 100]) }}
                </p>
                <a href="{{ route('admin.refunds.index') }}" class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                    {{ __('admin.finance.refund_cta') }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">{{ __('admin.finance.platform_total') }}</p>
                    <p class="mt-2 text-2xl font-bold text-brand-900">Rp {{ $fmt($totalPlatformFees) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.finance.gross_volume') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ $fmt($totalVolume) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">{{ __('admin.finance.history_title') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.finance.history_hint', [
                        'rate' => PlatformFee::RATE * 100,
                        'total' => PlatformFee::TOTAL_RATE * 100,
                    ]) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.finance.history_grouped_note', ['months' => (int) config('admin.finance.history_months', 24)]) }}</p>
                </div>
                @if ($history->isEmpty())
                    <p class="p-8 text-center text-sm text-slate-500">{{ __('admin.finance.history_empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            @include('admin.finance.partials.history-thead')
                            <tbody class="divide-y divide-slate-100">
                                @include('admin.finance.partials.history-groups-tbody', ['groups' => $history])
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">
                        {{ $history->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
