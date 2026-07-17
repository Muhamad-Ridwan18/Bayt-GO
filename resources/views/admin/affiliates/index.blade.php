@php use App\Support\IndonesianNumber; @endphp
<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Affiliate</h1>
                    <p class="mt-1 text-sm text-slate-600">Monitor affiliate, komisi, dan withdraw.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.affiliates.settings.edit') }}" class="ui-btn-secondary">Pengaturan</a>
                    <a href="{{ route('admin.affiliates.withdrawals.index') }}" class="ui-btn-secondary">Withdraw</a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Total affiliate</p>
                    <p class="mt-2 text-xl font-bold">{{ $stats['total_affiliate'] }} <span class="text-sm font-medium text-slate-500">({{ $stats['active_affiliate'] }} aktif)</span></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Total komisi</p>
                    <p class="mt-2 text-xl font-bold">Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['total_commission']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Pending komisi</p>
                    <p class="mt-2 text-xl font-bold text-amber-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['pending_commission']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Withdraw pending</p>
                    <p class="mt-2 text-xl font-bold">{{ $stats['pending_withdraw'] }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Affiliate</th>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Saldo</th>
                            <th class="px-4 py-3">Komisi</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($affiliates as $affiliate)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $affiliate->user?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $affiliate->user?->email }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono">{{ $affiliate->code }}</td>
                                <td class="px-4 py-3">{{ $affiliate->status->label() }}</td>
                                <td class="px-4 py-3 tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $affiliate->available_balance) }}</td>
                                <td class="px-4 py-3">{{ $affiliate->available_commissions_count }} / {{ $affiliate->commissions_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="font-semibold text-brand-700">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-slate-500">Belum ada affiliate.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $affiliates->links() }}
        </x-page-container>
    </div>
</x-app-layout>
