@php
    use App\Enums\AffiliateWithdrawalStatus;
    use App\Support\IndonesianNumber;
@endphp
<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Withdraw Affiliate</h1>
                <a href="{{ route('admin.affiliates.index') }}" class="ui-btn-secondary">Daftar Affiliate</a>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Affiliate</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Rekening</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($withdrawals as $w)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $w->affiliate?->user?->name }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $w->affiliate?->code }}</p>
                                </td>
                                <td class="px-4 py-3 tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $w->amount) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2.5">
                                        <x-bank-logo :code="$w->beneficiary_bank" size="sm" class="mt-0.5" />
                                        <div>
                                            <p>{{ $w->beneficiary_bank }} · {{ $w->beneficiary_account }}</p>
                                            <span class="text-xs text-slate-500">{{ $w->beneficiary_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $w->status->label() }}</td>
                                <td class="px-4 py-3 space-y-2">
                                    @if ($w->status === AffiliateWithdrawalStatus::Requested)
                                        <form method="POST" action="{{ route('admin.affiliates.withdrawals.approve', $w) }}">@csrf<button class="text-xs font-semibold text-emerald-700">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.affiliates.withdrawals.reject', $w) }}">@csrf<button class="text-xs font-semibold text-red-600">Reject</button></form>
                                    @endif
                                    @if ($w->status === AffiliateWithdrawalStatus::Approved)
                                        <form method="POST" action="{{ route('admin.affiliates.withdrawals.paid', $w) }}" enctype="multipart/form-data" class="space-y-1">
                                            @csrf
                                            <input type="file" name="transfer_proof" required accept=".jpg,.jpeg,.png,.webp,.pdf" class="text-xs">
                                            <button class="text-xs font-semibold text-emerald-700">Mark Paid</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.affiliates.withdrawals.failed', $w) }}">@csrf<button class="text-xs font-semibold text-red-600">Mark Failed</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-slate-500">Belum ada withdraw.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $withdrawals->links() }}
        </x-page-container>
    </div>
</x-app-layout>
