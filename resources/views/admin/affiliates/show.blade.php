@php
    use App\Enums\AffiliateBankVerificationStatus;
    use App\Support\IndonesianNumber;
@endphp
<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('admin.affiliates.index') }}" class="text-sm font-semibold text-brand-700">← Kembali</a>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $affiliate->user?->name }}</h1>
                    <p class="text-sm text-slate-600">{{ $affiliate->user?->email }} · <span class="font-mono">{{ $affiliate->code }}</span></p>
                </div>
                <form method="POST" action="{{ route('admin.affiliates.toggle', $affiliate) }}">
                    @csrf
                    <button class="ui-btn-secondary">{{ $affiliate->status->value === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Rekening</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($affiliate->bankAccounts as $bank)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <div class="flex items-start gap-3">
                                <x-bank-logo :code="$bank->bank_code" size="md" />
                                <div>
                                    <p class="font-semibold">{{ $bank->bank_name }} · {{ $bank->account_number }}</p>
                                    <p class="text-slate-600">{{ $bank->account_holder }} · {{ $bank->verification_status->label() }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @if ($bank->verification_status !== AffiliateBankVerificationStatus::Verified)
                                    <form method="POST" action="{{ route('admin.affiliates.banks.verify', $bank) }}">@csrf<button class="text-xs font-semibold text-emerald-700">Verifikasi</button></form>
                                @endif
                                @if ($bank->verification_status !== AffiliateBankVerificationStatus::Rejected)
                                    <form method="POST" action="{{ route('admin.affiliates.banks.reject', $bank) }}">@csrf<button class="text-xs font-semibold text-red-600">Tolak</button></form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-3 text-slate-500">Belum ada rekening.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Komisi</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Booking</th>
                                <th class="px-3 py-2">Base</th>
                                <th class="px-3 py-2">Rate</th>
                                <th class="px-3 py-2">Komisi</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($affiliate->commissions as $commission)
                                <tr>
                                    <td class="px-3 py-2">{{ $commission->booking?->booking_code }}</td>
                                    <td class="px-3 py-2">Rp {{ IndonesianNumber::formatThousands((string) (int) $commission->transaction_base_amount_snapshot) }}</td>
                                    <td class="px-3 py-2">{{ number_format((float) $commission->commission_rate_snapshot * 100, 2) }}%</td>
                                    <td class="px-3 py-2">Rp {{ IndonesianNumber::formatThousands((string) (int) $commission->commission_amount) }}</td>
                                    <td class="px-3 py-2">{{ $commission->status->label() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </x-page-container>
    </div>
</x-app-layout>
