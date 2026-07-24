@php
    use App\Enums\AffiliateWithdrawalStatus;
    use App\Support\IndonesianNumber;
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="font-semibold text-slate-900">Ringkasan withdraw affiliate</h3>
    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">{{ $pendingCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total nominal pending</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">
                Rp {{ IndonesianNumber::formatThousands((string) (int) $pendingAmount) }}
            </p>
        </div>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Affiliate</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Rekening</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($affiliateWithdrawals as $w)
                    @php
                        $tagClass = match ($w->status) {
                            AffiliateWithdrawalStatus::Requested => 'bg-amber-50 text-amber-900 ring-amber-200',
                            AffiliateWithdrawalStatus::Approved => 'bg-blue-50 text-blue-900 ring-blue-200',
                            AffiliateWithdrawalStatus::Paid => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                            AffiliateWithdrawalStatus::Rejected, AffiliateWithdrawalStatus::Failed => 'bg-red-50 text-red-900 ring-red-200',
                            default => 'bg-slate-50 text-slate-900 ring-slate-200',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                            {{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ $w->affiliate?->user?->name ?? '—' }}</p>
                            <p class="font-mono text-xs text-slate-500">{{ $w->affiliate?->code }}</p>
                        </td>
                        <td class="px-4 py-3 font-medium tabular-nums text-slate-900 whitespace-nowrap">
                            Rp {{ IndonesianNumber::formatThousands((string) (int) $w->amount) }}
                        </td>
                        <td class="px-4 py-3 text-slate-800">
                            <div class="flex items-start gap-2.5">
                                <x-bank-logo :code="$w->beneficiary_bank" size="sm" class="mt-0.5" />
                                <div>
                                    <p>{{ $w->beneficiary_bank }} · {{ $w->beneficiary_account }}</p>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $w->beneficiary_name }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tagClass }}">
                                {{ $w->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-col items-stretch gap-2 sm:min-w-[10rem]">
                                @if ($w->status === AffiliateWithdrawalStatus::Requested)
                                    <form method="POST" action="{{ route('admin.affiliates.withdrawals.approve', $w) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.affiliates.withdrawals.reject', $w) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                            Reject
                                        </button>
                                    </form>
                                @endif
                                @if ($w->status === AffiliateWithdrawalStatus::Approved)
                                    <form method="POST" action="{{ route('admin.affiliates.withdrawals.paid', $w) }}" enctype="multipart/form-data" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-left">
                                        @csrf
                                        <input type="file" name="transfer_proof" required accept=".jpg,.jpeg,.png,.webp,.pdf" class="block w-full text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-white file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 file:ring-1 file:ring-slate-200">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                            Mark Paid
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.affiliates.withdrawals.failed', $w) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                            Mark Failed
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada withdraw affiliate.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $affiliateWithdrawals->links() }}
</div>
